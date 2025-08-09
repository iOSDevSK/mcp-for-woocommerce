<?php //phpcs:ignore
/**
 * The WordPress MCP Streamable HTTP Transport class.
 *
 * @package WordPressMcp
 */

namespace Automattic\WordpressMcp\Core;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Exception;

/**
 * The WordPress MCP Streamable HTTP Transport class.
 * Uses JSON-RPC 2.0 format for direct streamable connections.
 */
class McpStreamableTransport extends McpTransportBase {

	/**
	 * The request ID.
	 *
	 * @var int
	 */
	private int $request_id = 0;

	/**
	 * Initialize the class and register routes
	 *
	 * @param WpMcp $mcp The WordPress MCP instance.
	 */
	public function __construct( WpMcp $mcp ) {
		parent::__construct( $mcp );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'rest_pre_serve_request', array( $this, 'handle_cors_preflight' ), 10, 4 );
	}

	/**
	 * Register all MCP proxy routes
	 */
	public function register_routes(): void {
		// If MCP is disabled, don't register routes.
		if ( ! $this->is_mcp_enabled() ) {
			return;
		}

		// Single endpoint for all MCP operations.
		register_rest_route(
			'wp/v2',
			'/wpmcp/streamable',
			array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);


		// OpenAPI spec endpoint
		register_rest_route(
			'wp/v2',
			'/wpmcp/openapi.json',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'openapi_spec' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Check if the user has permission to access the MCP API
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission(): WP_Error|bool {
		try {
			error_log( '[MCP AUTH] Permission check started' );
			error_log( '[MCP AUTH] Request URI: ' . ( $_SERVER['REQUEST_URI'] ?? 'not set' ) );
			error_log( '[MCP AUTH] Request method: ' . ( $_SERVER['REQUEST_METHOD'] ?? 'not set' ) );
			error_log( '[MCP AUTH] User agent: ' . ( $_SERVER['HTTP_USER_AGENT'] ?? 'not set' ) );
			
			// If MCP is disabled, deny access.
			if ( ! $this->is_mcp_enabled() ) {
				error_log( '[MCP AUTH] MCP is disabled in settings' );
				return new WP_Error(
					'mcp_disabled',
					'MCP functionality is currently disabled.',
					array( 'status' => 403 )
				);
			}
			
			// Check JWT required setting
			$jwt_required = function_exists( 'get_option' ) ? (bool) get_option( 'wordpress_mcp_jwt_required', true ) : true;
			
			error_log( '[MCP AUTH] MCP enabled: true' );
			error_log( '[MCP AUTH] JWT required: ' . ( $jwt_required ? 'true' : 'false' ) );
			error_log( '[MCP AUTH] User logged in: ' . ( is_user_logged_in() ? 'true' : 'false' ) );
			error_log( '[MCP AUTH] Current user ID: ' . get_current_user_id() );
			
			if ( ! $jwt_required ) {
				// JWT is disabled, allow access without authentication (readonly mode)
				error_log( '[MCP AUTH] JWT disabled - ALLOWING ACCESS' );
				return true;
			}
			
			// JWT is required, check if user is authenticated via JWT or cookies
			// The JWT authentication is handled by the rest_authentication_errors filter
			// which runs before permission callbacks
			$result = is_user_logged_in();
			error_log( '[MCP AUTH] JWT required mode - permission result: ' . ( $result ? 'ALLOWED' : 'DENIED' ) );
			
			if ( ! $result ) {
				error_log( '[MCP AUTH] AUTHENTICATION FAILED - returning WP_Error' );
				return new WP_Error(
					'rest_forbidden',
					'You do not have permission to access this endpoint.',
					array( 'status' => 403 )
				);
			}
			
			return $result;
			
		} catch ( Exception $e ) {
			// Log any exceptions
			error_log( '[MCP AUTH] EXCEPTION in permission check: ' . $e->getMessage() );
			error_log( '[MCP AUTH] Exception trace: ' . $e->getTraceAsString() );
			return new WP_Error(
				'permission_check_error',
				'Error checking permissions: ' . $e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Handle the HTTP request
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {
		error_log( '[MCP HANDLE] Request received' );
		error_log( '[MCP HANDLE] Method: ' . $request->get_method() );
		error_log( '[MCP HANDLE] Route: ' . $request->get_route() );
		error_log( '[MCP HANDLE] Headers: ' . print_r( $request->get_headers(), true ) );
		
		// Handle preflight requests
		if ( 'OPTIONS' === $request->get_method() ) {
			error_log( '[MCP HANDLE] Handling OPTIONS preflight' );
			return new WP_REST_Response( null, 204 );
		}

		$method = $request->get_method();

		if ( 'POST' === $method ) {
			error_log( '[MCP HANDLE] Handling POST request' );
			return $this->handle_post_request( $request );
		}

		// Health-check friendly GET/HEAD responses and SSE fallback (only when JWT is OFF)
		if ( 'GET' === $method ) {
			$accept = $request->get_header( 'accept' );
			$jwt_required = function_exists( 'get_option' ) ? (bool) get_option( 'wordpress_mcp_jwt_required', true ) : true;
			// If client requests SSE and JWT is OFF, provide legacy SSE "endpoint" event for compatibility
			if ( ! $jwt_required && $accept && strpos( $accept, 'text/event-stream' ) !== false ) {
				// Manually emit SSE headers and body
				header( 'Content-Type: text/event-stream' );
				header( 'Cache-Control: no-cache' );
				header( 'Connection: keep-alive' );
				header( 'MCP-Protocol-Version: 2025-06-18' );
				// Send endpoint event per 2024-11-05 SSE transport
				echo "event: endpoint\n";
				echo "data: {\"endpoint\": \"" . esc_url_raw( rest_url( 'wp/v2/wpmcp/streamable' ) ) . "\"}\n\n";
				flush();
				// Keep the SSE stream open for a short period with periodic pings so clients can complete handshake
				$start = time();
				while ( ( time() - $start ) < 60 ) { // keep open up to 60 seconds
					echo ": ping\n\n";
					flush();
					usleep( 10000000 ); // 10 seconds
				}
				exit;
			}
			// Default JSON health when not requesting SSE (or when JWT is ON)
			$body = array(
				'jsonrpc' => '2.0',
				'result'  => array(
					'status'    => 'ok',
					'transport' => 'streamable-http',
					'endpoint'  => '/wp/v2/wpmcp/streamable',
				),
			);
			$headers = array(
				'Content-Type' => 'application/json',
				'MCP-Protocol-Version' => '2025-06-18',
			);
			return new WP_REST_Response( $body, 200, $headers );
		}

		if ( 'HEAD' === $method ) {
			$headers = array(
				'MCP-Protocol-Version' => '2025-06-18',
			);
			return new WP_REST_Response( null, 200, $headers );
		}

		// Return 405 for unsupported methods.
		return new WP_REST_Response(
			McpErrorHandler::create_error_response( 0, McpErrorHandler::INVALID_REQUEST, 'Method not allowed' ),
			405
		);
	}

	/**
	 * Handle POST requests
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	private function handle_post_request( $request ) {
		try {
			// Log incoming request for Claude.ai debugging
			$this->log_claude_request( $request );
			
			// Check if JWT is disabled - if so, use PHP proxy mode for external access
			$jwt_required = function_exists( 'get_option' ) ? (bool) get_option( 'wordpress_mcp_jwt_required', true ) : true;
			if ( ! $jwt_required ) {
				return $this->handle_php_proxy_mode( $request );
			}
            // Validate Accept header - relax to default JSON when missing or */*
            $accept_header = $request->get_header( 'accept' );
            $accept_header = is_string( $accept_header ) ? trim( $accept_header ) : '';
            $accepts_json = ( $accept_header === '' )
                || strpos( $accept_header, 'application/json' ) !== false
                || strpos( $accept_header, '*/*' ) !== false;
            $accepts_sse  = $accept_header && strpos( $accept_header, 'text/event-stream' ) !== false;
            if ( ! $accepts_json && ! $accepts_sse ) {
                // Still incompatible: log and continue with JSON default instead of hard 400
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[MCP Streamable] Non-compatible Accept header received: ' . $accept_header . ' - proceeding as application/json' );
                }
            }

			// Check for Claude.ai beta header requirement
			$beta_header = $request->get_header( 'anthropic-beta' );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[MCP Streamable] Beta header: ' . ( $beta_header ? $beta_header : 'missing' ) );
			}

			// Validate content type - be more flexible with content-type headers
			$content_type = $request->get_header( 'content-type' );
			if ( $content_type && strpos( $content_type, 'application/json' ) === false ) {
				return new WP_REST_Response(
					McpErrorHandler::invalid_content_type( 0 ),
					400
				);
			}

			// Get the JSON-RPC message(s) - can be single message or array batch
			$body = $request->get_json_params();
			if ( null === $body ) {
				return new WP_REST_Response(
					McpErrorHandler::parse_error( 0, 'Invalid JSON in request body' ),
					400
				);
			}

			// Handle both single messages and batched arrays
			$messages                       = is_array( $body ) && isset( $body[0] ) ? $body : array( $body );
			$has_requests                   = false;
			$has_notifications_or_responses = false;

			// Validate all messages and categorize them
			foreach ( $messages as $message ) {
				$validation_result = McpErrorHandler::validate_jsonrpc_message( $message );
				if ( true !== $validation_result ) {
					McpErrorHandler::log_error( 
						'JSON-RPC validation failed for incoming message', 
						array( 
							'message' => $message, 
							'validation_error' => $validation_result 
						) 
					);
					return new WP_REST_Response( $validation_result, 400 );
				}

				// Check if it's a request (has id and method) or notification/response
				if ( isset( $message['method'] ) && isset( $message['id'] ) ) {
					$has_requests = true;
				} else {
					$has_notifications_or_responses = true;
				}
			}

			// If only notifications or responses, return 202 Accepted with no body
			if ( $has_notifications_or_responses && ! $has_requests ) {
				return new WP_REST_Response( null, 202 );
			}

			// Process requests and return JSON response
			$results        = array();
			$has_initialize = false;
			foreach ( $messages as $message ) {
				if ( isset( $message['method'] ) && isset( $message['id'] ) ) {
					$this->request_id = (int) $message['id'];
					if ( 'initialize' === $message['method'] ) {
						$has_initialize = true;
					}
					$results[] = $this->process_message( $message );
				}
			}

			// Return single result or batch
			$response_body = count( $results ) === 1 ? $results[0] : $results;

			// Log outgoing response for Claude.ai debugging
			$this->log_claude_response( $response_body );

			// Validate outgoing response
			if ( is_array( $response_body ) ) {
				$responses_to_validate = isset( $response_body[0] ) ? $response_body : array( $response_body );
				foreach ( $responses_to_validate as $response ) {
					$validation_result = McpErrorHandler::validate_jsonrpc_message( $response );
					if ( true !== $validation_result ) {
						McpErrorHandler::log_error( 
							'Invalid JSON-RPC response being sent', 
							array( 
								'response' => $response,
								'validation_error' => $validation_result
							) 
						);
					}
				}
			}

				$headers = array(
					'Content-Type'                 => 'application/json',
					'MCP-Protocol-Version'         => '2025-06-18',
					// Removed dangerous CORS headers for security
				);

				// If this batch included initialize, assign a session ID per spec (optional for clients)
				if ( $has_initialize ) {
					if ( function_exists( 'wp_generate_uuid4' ) ) {
						$headers['Mcp-Session-Id'] = wp_generate_uuid4();
					} else {
						$headers['Mcp-Session-Id'] = bin2hex( random_bytes( 16 ) );
					}
				}

				return new WP_REST_Response( $response_body, 200, $headers );
		} catch ( \Throwable $exception ) {
			// Handle any unexpected exceptions
			McpErrorHandler::log_error( 'Unexpected error in handle_post_request', array( 'exception' => $exception->getMessage() ) );
			return new WP_REST_Response(
				McpErrorHandler::handle_exception( $exception, $this->request_id ),
				500
			);
		}
	}

	/**
	 * Process a JSON-RPC message
	 *
	 * @param array $message The JSON-RPC message.
	 * @return array
	 */
	private function process_message( array $message ): array {
		$this->request_id = (int) $message['id'];
		$params           = $message['params'] ?? array();

		// Route the request using the base class
		$result = $this->route_request( $message['method'], $params, $this->request_id );

		// Check if the result contains an error
		if ( isset( $result['error'] ) ) {
			return $this->format_error_response( $result, $this->request_id );
		}

		return $this->format_success_response( $result, $this->request_id );
	}

	/**
	 * Create a method not found error (JSON-RPC 2.0 format)
	 *
	 * @param string $method The method that was not found.
	 * @param int    $request_id The request ID.
	 * @return array
	 */
	protected function create_method_not_found_error( string $method, int $request_id ): array {
		$error_response = McpErrorHandler::method_not_found( $request_id, $method );
		return array(
			'error' => $error_response['error'],
		);
	}

	/**
	 * Handle exceptions that occur during request processing (JSON-RPC 2.0 format)
	 *
	 * @param \Throwable $exception The exception.
	 * @param int        $request_id The request ID.
	 * @return array
	 */
	protected function handle_exception( \Throwable $exception, int $request_id ): array {
		$error_response = McpErrorHandler::handle_exception( $exception, $request_id );
		return array(
			'error' => $error_response['error'],
		);
	}

	/**
	 * Format a successful response (JSON-RPC 2.0 format)
	 *
	 * @param array $result The result data.
	 * @param int   $request_id The request ID.
	 * @return array
	 */
	protected function format_success_response( array $result, int $request_id = 0 ): array {
		$response = array(
			'jsonrpc' => '2.0',
			'id'      => $request_id,
			'result'  => $result,
		);

		return $response;
	}

	/**
	 * Format an error response (JSON-RPC 2.0 format)
	 *
	 * @param array $error The error data.
	 * @param int   $request_id The request ID.
	 * @return array
	 */
	protected function format_error_response( array $error, int $request_id = 0 ): array {
		// If the error already contains a proper error structure
		if ( isset( $error['error'] ) && is_array( $error['error'] ) ) {
			$response = array(
				'jsonrpc' => '2.0',
				'id'      => $request_id,
				'error'   => $error['error'],
			);
			
			// Validate the error structure has required fields
			if ( ! isset( $error['error']['code'] ) || ! isset( $error['error']['message'] ) ) {
				McpErrorHandler::log_error( 
					'Error response missing required fields', 
					array( 'error' => $error['error'] ) 
				);
				return McpErrorHandler::internal_error( $request_id, 'Invalid error response format' );
			}
			
			return $response;
		}

		// Log the invalid error format for debugging
		McpErrorHandler::log_error( 
			'Invalid error response format received', 
			array( 'error' => $error ) 
		);
		
		// If it's not already a proper error response, make it one
		return McpErrorHandler::internal_error( $request_id, 'Invalid error response format' );
	}

	/**
	 * Handle CORS preflight requests for MCP endpoint.
	 *
	 * @param mixed           $served  Whether the request has been served.
	 * @param WP_REST_Response $result  The response object.
	 * @param WP_REST_Request $request The request object.
	 * @param WP_REST_Server  $server  The REST server instance.
	 * @return mixed
	 */
	public function handle_cors_preflight( $served, $result, $request, $server ) {
		// Debug log all CORS requests
		error_log( '[MCP CORS] Request route: ' . $request->get_route() );
		error_log( '[MCP CORS] Request method: ' . $request->get_method() );
		error_log( '[MCP CORS] Request headers: ' . print_r( $request->get_headers(), true ) );
		
		// Only handle our MCP endpoint
		if ( strpos( $request->get_route(), '/wpmcp/streamable' ) === false ) {
			error_log( '[MCP CORS] Not MCP endpoint, skipping CORS' );
			return $served;
		}

		error_log( '[MCP CORS] Setting CORS headers for MCP endpoint' );
		
		// Set CORS headers for all requests to our endpoint - allow all domains with Claude.ai specific headers
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS, HEAD' );
		header( 'Access-Control-Allow-Headers: content-type, accept, anthropic-beta, authorization, mcp-protocol-version, mcp-session-id, user-agent, cache-control, pragma' );
		header( 'Access-Control-Expose-Headers: MCP-Protocol-Version, Mcp-Session-Id' );
		header( 'Access-Control-Max-Age: 1800' );
		header( 'Access-Control-Allow-Credentials: false' );

		// Handle OPTIONS preflight request
		if ( $request->get_method() === 'OPTIONS' ) {
			error_log( '[MCP CORS] Handling OPTIONS preflight request' );
			http_response_code( 204 );
			exit;
		}

		return $served;
	}

	/**
	 * Handle PHP proxy mode when JWT is disabled
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	private function handle_php_proxy_mode( $request ) {
		error_log( '[MCP PROXY] PHP proxy mode activated' );
		error_log( '[MCP PROXY] Request method: ' . $request->get_method() );
		error_log( '[MCP PROXY] Request headers: ' . print_r( $request->get_headers(), true ) );
		
		// Get the request body
		$body = $request->get_body();
		error_log( '[MCP PROXY] Request body length: ' . strlen( $body ) );
		error_log( '[MCP PROXY] Request body: ' . $body );
		
		if ( empty( $body ) ) {
			error_log( '[MCP PROXY] Empty request body - returning error' );
			return new WP_REST_Response(
				array(
					'jsonrpc' => '2.0',
					'id' => null,
					'error' => array(
						'code' => -32700,
						'message' => 'Parse error: Empty request body'
					)
				),
				400
			);
		}

		// Decode JSON request
		$data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_REST_Response(
				array(
					'jsonrpc' => '2.0',
					'id' => null,
					'error' => array(
						'code' => -32700,
						'message' => 'Parse error: ' . json_last_error_msg()
					)
				),
				400
			);
		}

		$id = $data['id'] ?? null;

		// Process the MCP request internally
		try {
			$method = $data['method'] ?? '';
			$params = $data['params'] ?? array();

			// Route the request using existing MCP routing
			$result = $this->route_request( $method, $params, (int) $id );

			// Format response according to JSON-RPC 2.0
			if ( isset( $result['error'] ) ) {
				// Ensure error code is integer for Claude Desktop compatibility
				$error = $result['error'];
				if ( isset( $error['code'] ) && ! is_int( $error['code'] ) ) {
					$error['code'] = (int) $error['code'];
				}
				$error_response = array(
					'jsonrpc' => '2.0',
					'id' => $id,
					'error' => $error
				);
				
				// Log error response from PHP proxy mode
				$this->log_claude_response( $error_response );
				
				return new WP_REST_Response( $error_response, 200 );
			} else {
				$response_body = array(
					'jsonrpc' => '2.0',
					'id' => $id,
					'result' => $result
				);
				
				// Log response from PHP proxy mode
				$this->log_claude_response( $response_body );
				
				return new WP_REST_Response( $response_body, 200 );
			}
		} catch ( Exception $e ) {
			$exception_response = array(
				'jsonrpc' => '2.0',
				'id' => $id,
				'error' => array(
					'code' => -32603,
					'message' => 'Internal error: ' . $e->getMessage()
				)
			);
			
			// Log exception response from PHP proxy mode
			$this->log_claude_response( $exception_response );
			
			return new WP_REST_Response( $exception_response, 500 );
		}
	}

	/**
	 * Log incoming Claude.ai request for debugging
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	private function log_claude_request( $request ) {
		$headers = $request->get_headers();
		$body = $request->get_body();
		$params = $request->get_params();
		$method = $request->get_method();
		$url = $request->get_route();
		
		$user_agent = $headers['user_agent'][0] ?? '';
		$accept = $headers['accept'][0] ?? '';
		$content_type = $headers['content_type'][0] ?? '';
		$anthropic_beta = $headers['anthropic_beta'][0] ?? '';
		$authorization = !empty($headers['authorization']) ? '[PRESENT]' : '[MISSING]';
		
		// Detect connection source based on user agent and other indicators
		$connection_source = $this->detect_connection_source($headers);
		
		$log_data = array(
			'timestamp' => current_time('mysql'),
			'connection_source' => $connection_source,
			'method' => $method,
			'url' => $url,
			'user_agent' => $user_agent,
			'accept' => $accept,
			'content_type' => $content_type,
			'anthropic_beta' => $anthropic_beta,
			'authorization' => $authorization,
			'body_length' => strlen($body),
			'body' => $body,
			'params' => $params,
			'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
			'referer' => $_SERVER['HTTP_REFERER'] ?? '',
			'all_headers' => $headers
		);
		
		// Enhanced connection attempt logging
		$this->log_connection_attempt($log_data);
		
		// Log to WordPress debug.log if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[MCP Claude.ai REQUEST] ' . wp_json_encode( $log_data, JSON_PRETTY_PRINT ) );
		}
		
		// Also log to separate file for easier debugging
		$log_file = WP_CONTENT_DIR . '/mcp-claude-debug.log';
		$log_entry = "[" . date('Y-m-d H:i:s') . "] CLAUDE.AI REQUEST:\n" . wp_json_encode( $log_data, JSON_PRETTY_PRINT ) . "\n\n";
		error_log( $log_entry, 3, $log_file );
	}

	/**
	 * Log outgoing response for Claude.ai debugging
	 *
	 * @param mixed $response_body The response body.
	 */
	private function log_claude_response( $response_body ) {
		$log_data = array(
			'timestamp' => current_time('mysql'),
			'response_type' => gettype($response_body),
			'response_body' => $response_body,
			'success' => !isset($response_body['error']),
			'error_code' => isset($response_body['error']['code']) ? $response_body['error']['code'] : null,
			'error_message' => isset($response_body['error']['message']) ? $response_body['error']['message'] : null
		);
		
		// Enhanced connection result logging
		$this->log_connection_result($log_data);
		
		// Log to WordPress debug.log if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[MCP Claude.ai RESPONSE] ' . wp_json_encode( $log_data, JSON_PRETTY_PRINT ) );
		}
		
		// Also log to separate file for easier debugging
		$log_file = WP_CONTENT_DIR . '/mcp-claude-debug.log';
		$log_entry = "[" . date('Y-m-d H:i:s') . "] CLAUDE.AI RESPONSE:\n" . wp_json_encode( $log_data, JSON_PRETTY_PRINT ) . "\n" . str_repeat('-', 80) . "\n\n";
		error_log( $log_entry, 3, $log_file );
	}

	/**
	 * Detect connection source based on headers and other indicators
	 *
	 * @param array $headers Request headers.
	 * @return string
	 */
	private function detect_connection_source( $headers ) {
		$user_agent = $headers['user_agent'][0] ?? '';
		$anthropic_beta = $headers['anthropic_beta'][0] ?? '';
		$referer = $_SERVER['HTTP_REFERER'] ?? '';
		
		// Claude.ai web app detection
		if ( strpos( $user_agent, 'claude' ) !== false || 
			 strpos( $user_agent, 'Claude' ) !== false ||
			 strpos( $referer, 'claude.ai' ) !== false ||
			 !empty( $anthropic_beta ) ) {
			return 'claude.ai-webapp';
		}
		
		// Claude Desktop detection
		if ( strpos( $user_agent, 'Claude Desktop' ) !== false ||
			 strpos( $user_agent, 'claude-desktop' ) !== false ) {
			return 'claude-desktop';
		}
		
		// MCP proxy detection
		if ( strpos( $user_agent, 'mcp' ) !== false ||
			 strpos( $user_agent, 'MCP' ) !== false ) {
			return 'mcp-proxy';
		}
		
		// Generic HTTP client detection
		if ( strpos( $user_agent, 'curl' ) !== false ) {
			return 'curl';
		}
		
		if ( strpos( $user_agent, 'Postman' ) !== false ) {
			return 'postman';
		}
		
		if ( empty( $user_agent ) ) {
			return 'unknown-no-ua';
		}
		
		return 'unknown-' . substr( $user_agent, 0, 20 );
	}
	
	/**
	 * Log connection attempt with enhanced details
	 *
	 * @param array $log_data Connection log data.
	 */
	private function log_connection_attempt( $log_data ) {
		$connection_log = array(
			'event' => 'connection_attempt',
			'timestamp' => current_time('mysql'),
			'source' => $log_data['connection_source'],
			'method' => $log_data['method'],
			'remote_addr' => $log_data['remote_addr'],
			'user_agent' => $log_data['user_agent'],
			'endpoint' => 'https://woo.webtalkbot.com' . $log_data['url'],
			'has_auth' => $log_data['authorization'] === '[PRESENT]',
			'anthropic_beta' => $log_data['anthropic_beta'],
			'request_size' => $log_data['body_length']
		);
		
		// Log to dedicated connection log file
		$connection_log_file = WP_CONTENT_DIR . '/mcp-connections.log';
		$connection_entry = "[" . date('Y-m-d H:i:s') . "] CONNECTION_ATTEMPT: " . wp_json_encode( $connection_log ) . "\n";
		error_log( $connection_entry, 3, $connection_log_file );
		
		// Also log failed connections to separate error log
		if ( $log_data['connection_source'] === 'claude.ai-webapp' ) {
			$claude_connection_log = WP_CONTENT_DIR . '/mcp-claude-connections.log';
			$claude_entry = "[" . date('Y-m-d H:i:s') . "] CLAUDE.AI_CONNECTION: " . wp_json_encode( $connection_log ) . "\n";
			error_log( $claude_entry, 3, $claude_connection_log );
		}
	}
	
	/**
	 * Log connection result with success/failure details
	 *
	 * @param array $log_data Response log data.
	 */
	private function log_connection_result( $log_data ) {
		$result_log = array(
			'event' => 'connection_result',
			'timestamp' => current_time('mysql'),
			'success' => $log_data['success'],
			'error_code' => $log_data['error_code'],
			'error_message' => $log_data['error_message'],
			'response_type' => $log_data['response_type']
		);
		
		// Log connection results
		$connection_log_file = WP_CONTENT_DIR . '/mcp-connections.log';
		$result_entry = "[" . date('Y-m-d H:i:s') . "] CONNECTION_RESULT: " . wp_json_encode( $result_log ) . "\n";
		error_log( $result_entry, 3, $connection_log_file );
		
		// Log failed connections separately for easier analysis
		if ( !$log_data['success'] ) {
			$failed_log_file = WP_CONTENT_DIR . '/mcp-connection-failures.log';
			$failed_entry = "[" . date('Y-m-d H:i:s') . "] FAILED_CONNECTION: " . wp_json_encode( $result_log ) . "\n";
			error_log( $failed_entry, 3, $failed_log_file );
		}
	}


	/**
	 * Generate OpenAPI specification for MCP tools
	 *
	 * @return WP_REST_Response
	 */
	public function openapi_spec() {
		$site_url = get_site_url();
		$spec = array(
			'openapi' => '3.0.1',
			'info' => array(
				'title' => 'WooCommerce MCP API',
				'description' => 'Model Context Protocol (MCP) integration for WooCommerce stores',
				'version' => WORDPRESS_MCP_VERSION,
				'contact' => array(
					'email' => get_option( 'admin_email', 'admin@' . parse_url( $site_url, PHP_URL_HOST ) )
				)
			),
			'servers' => array(
				array(
					'url' => $site_url,
					'description' => 'WooCommerce MCP Server'
				)
			),
			'paths' => array(
				'/wp-json/wp/v2/wpmcp/streamable' => array(
					'post' => array(
						'operationId' => 'mcpJsonRpcCall',
						'summary' => 'MCP JSON-RPC endpoint for advanced queries',
						'description' => 'Advanced Model Context Protocol endpoint using JSON-RPC 2.0. Use this for complex WooCommerce operations like detailed product searches, order management, and data analysis.',
						'requestBody' => array(
							'required' => true,
							'content' => array(
								'application/json' => array(
									'schema' => array(
										'$ref' => '#/components/schemas/JsonRpcRequest'
									)
								)
							)
						),
						'responses' => array(
							'200' => array(
								'description' => 'Successful response',
								'content' => array(
									'application/json' => array(
										'schema' => array(
											'$ref' => '#/components/schemas/JsonRpcResponse'
										)
									)
								)
							)
						)
					),
					'get' => array(
						'operationId' => 'searchWooCommerce',
						'summary' => 'Search WooCommerce store data',
						'description' => 'Use this tool to search for information in the WooCommerce store. Perfect for queries about products, order status, or customers. Example queries: \'find all orders for email john.doe@example.com\', \'what products are in the electronics category?\', \'show product details for ID 123\', \'search for laptop products\', \'get recent orders\'.',
						'parameters' => array(
							array(
								'name' => 'query',
								'in' => 'query',
								'description' => 'Specific search query. Examples: \'latest orders\', \'products under $50\', \'customer John Smith\', \'laptops in stock\', \'orders from last week\'.',
								'required' => true,
								'schema' => array(
									'type' => 'string'
								)
							)
						),
						'responses' => array(
							'200' => array(
								'description' => 'Successful response with found data.',
								'content' => array(
									'application/json' => array(
										'schema' => array(
											'type' => 'object',
											'properties' => array(
												'status' => array( 'type' => 'string' ),
												'transport' => array( 'type' => 'string' ),
												'endpoint' => array( 'type' => 'string' )
											)
										)
									)
								)
							)
						)
					)
				)
			),
			'components' => array(
				'schemas' => array(
					'JsonRpcRequest' => array(
						'type' => 'object',
						'required' => array( 'jsonrpc', 'method', 'id' ),
						'properties' => array(
							'jsonrpc' => array( 'type' => 'string', 'enum' => array( '2.0' ) ),
							'method' => array( 'type' => 'string' ),
							'params' => array( 'type' => 'object' ),
							'id' => array( 'oneOf' => array(
								array( 'type' => 'string' ),
								array( 'type' => 'integer' ),
								array( 'type' => 'null' )
							) )
						)
					),
					'JsonRpcResponse' => array(
						'type' => 'object',
						'required' => array( 'jsonrpc', 'id' ),
						'properties' => array(
							'jsonrpc' => array( 'type' => 'string', 'enum' => array( '2.0' ) ),
							'result' => array( 'type' => 'object' ),
							'error' => array(
								'type' => 'object',
								'properties' => array(
									'code' => array( 'type' => 'integer' ),
									'message' => array( 'type' => 'string' ),
									'data' => array( 'type' => 'object' )
								)
							),
							'id' => array( 'oneOf' => array(
								array( 'type' => 'string' ),
								array( 'type' => 'integer' ),
								array( 'type' => 'null' )
							) )
						)
					)
				)
			)
		);

		return new WP_REST_Response( $spec, 200, array(
			'Content-Type' => 'application/json',
			'Access-Control-Allow-Origin' => '*',
		) );
	}

}

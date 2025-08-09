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
	}

	/**
	 * Check if the user has permission to access the MCP API
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission(): WP_Error|bool {
		try {
			// If MCP is disabled, deny access.
			if ( ! $this->is_mcp_enabled() ) {
				return new WP_Error(
					'mcp_disabled',
					'MCP functionality is currently disabled.',
					array( 'status' => 403 )
				);
			}
			
			// Check JWT required setting
			$jwt_required = function_exists( 'get_option' ) ? (bool) get_option( 'wordpress_mcp_jwt_required', true ) : true;
			
			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[MCP Streamable] JWT required: ' . ( $jwt_required ? 'true' : 'false' ) );
				error_log( '[MCP Streamable] User logged in: ' . ( is_user_logged_in() ? 'true' : 'false' ) );
			}
			
			if ( ! $jwt_required ) {
				// JWT is disabled, allow access without authentication (readonly mode)
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[MCP Streamable] JWT disabled - allowing access' );
				}
				return true;
			}
			
			// JWT is required, check if user is authenticated via JWT or cookies
			// The JWT authentication is handled by the rest_authentication_errors filter
			// which runs before permission callbacks
			$result = is_user_logged_in();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[MCP Streamable] Permission check result: ' . ( $result ? 'true' : 'false' ) );
			}
			return $result;
			
		} catch ( Exception $e ) {
			// Log any exceptions
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[MCP Streamable] Permission check exception: ' . $e->getMessage() );
			}
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
		// Handle preflight requests
		if ( 'OPTIONS' === $request->get_method() ) {
			return new WP_REST_Response( null, 204 );
		}

		$method = $request->get_method();

		if ( 'POST' === $method ) {
			return $this->handle_post_request( $request );
		}

		// Health-check friendly GET/HEAD responses for Claude.ai Connectors
		if ( 'GET' === $method ) {
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
				'MCP-Protocol-Version' => '2025-03-26',
			);
			return new WP_REST_Response( $body, 200, $headers );
		}

		if ( 'HEAD' === $method ) {
			$headers = array(
				'MCP-Protocol-Version' => '2025-03-26',
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
			// Check if JWT is disabled - if so, use PHP proxy mode for external access
			$jwt_required = function_exists( 'get_option' ) ? (bool) get_option( 'wordpress_mcp_jwt_required', true ) : true;
			if ( ! $jwt_required ) {
				return $this->handle_php_proxy_mode( $request );
			}
			// Validate Accept header - be flexible for Claude.ai compatibility
			$accept_header = $request->get_header( 'accept' );
			if ( ! $accept_header || strpos( $accept_header, 'application/json' ) === false ) {
				return new WP_REST_Response(
					McpErrorHandler::invalid_accept_header( 0 ),
					400
				);
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
				// Removed dangerous CORS headers for security
			);

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
		// Only handle our MCP endpoint
		if ( strpos( $request->get_route(), '/wpmcp/streamable' ) === false ) {
			return $served;
		}

		// Set CORS headers for all requests to our endpoint - allow all domains
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
		header( 'Access-Control-Allow-Headers: content-type, accept, anthropic-beta, authorization, mcp-protocol-version, mcp-session-id' );
		header( 'Access-Control-Expose-Headers: MCP-Protocol-Version, Mcp-Session-Id' );
		header( 'Access-Control-Max-Age: 600' );

		// Handle OPTIONS preflight request
		if ( $request->get_method() === 'OPTIONS' ) {
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
		// Get the request body
		$body = $request->get_body();
		if ( empty( $body ) ) {
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
				return new WP_REST_Response(
					array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'error' => $error
					),
					200
				);
			} else {
				return new WP_REST_Response(
					array(
						'jsonrpc' => '2.0',
						'id' => $id,
						'result' => $result
					),
					200
				);
			}
		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'jsonrpc' => '2.0',
					'id' => $id,
					'error' => array(
						'code' => -32603,
						'message' => 'Internal error: ' . $e->getMessage()
					)
				),
				500
			);
		}
	}
}

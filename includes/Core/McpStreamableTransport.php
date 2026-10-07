<?php
/**
 * The WordPress MCP Streamable HTTP Transport class.
 *
 * @package WordPressMcp
 */



namespace McpForWoo\Core;

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
				// Intentionally public: every tool returns only storefront data a
				// visitor can already see, nothing is written, and no caller is ever
				// signed in. The route exists only while "Enable MCP" is on.
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle the HTTP request for the Streamable HTTP transport.
	 *
	 * Every branch terminates the request itself via send_response()/send_error_response().
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return void
	 */
	public function handle_request( WP_REST_Request $request ) {
		
		// Handle preflight requests
		if ( 'OPTIONS' === $request->get_method() ) {
			$this->send_response( null, 204 );
			return;
		}

		$method = $request->get_method();

		if ( 'POST' === $method ) {
			$this->handle_streamable_post_request( $request );
			return;
		}

		// Health-check
		if ( 'GET' === $method ) {
			$accept = $request->get_header( 'accept' );
			
			// For streamable transport, require proper Accept header
			if ( ! $this->validate_streamable_headers( $request ) ) {
				$this->send_error_response(
					McpErrorHandler::invalid_accept_header( 0 ),
					400
				);
				return;
			}
			
			// Health response
			$body = array(
				'jsonrpc' => '2.0',
				'result'  => array(
					'status'    => 'ok',
					'transport' => 'streamable-http',
					'endpoint'  => '/wp/v2/wpmcp/streamable',
					'streaming' => true,
				),
			);
			$this->send_response( $body, 200 );
			return;
		}

		if ( 'HEAD' === $method ) {
			$this->send_response( null, 200, array(
				'MCP-Protocol-Version' => '2025-06-18',
				'X-Transport-Type' => 'streamable-http'
			) );
			return;
		}

		// Return 405 for unsupported methods
		$this->send_error_response(
			McpErrorHandler::create_error_response( 0, McpErrorHandler::INVALID_REQUEST, 'Method not allowed' ),
			405
		);
	}

	/**
	 * Validate streamable headers according to MCP specification
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool
	 */
	private function validate_streamable_headers( WP_REST_Request $request ): bool {
		$accept_header = $request->get_header( 'accept' );
		
		if ( ! $accept_header ) {
			return false;
		}
		
		// Require both application/json and text/event-stream for streamable transport
		return strpos( $accept_header, 'application/json' ) !== false &&
		       strpos( $accept_header, 'text/event-stream' ) !== false;
	}

	/**
	 * Send a complete JSON response to the client and end the request.
	 *
	 * The MCP Streamable HTTP transport allows a single, complete `application/json`
	 * body as the response to a POST, which is what this transport returns. Transfer
	 * encoding is a hop-by-hop concern owned by the web server / SAPI layer: an
	 * application must never write chunk framing into the response body, nor set the
	 * `Transfer-Encoding` / `Connection` headers itself. Doing so corrupted every
	 * response for spec-compliant clients (GitHub issue #5).
	 *
	 * @param mixed $data Response data. Null sends an empty body.
	 * @param int   $status HTTP status code.
	 * @param array $headers Additional headers.
	 */
	private function send_response( $data = null, int $status = 200, array $headers = array() ) {
		// Discard any buffered output so nothing is prepended to the JSON body.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a non-removable buffer must not emit a notice into the response body.
		while ( ob_get_level() > 0 && @ob_end_clean() ) {
			continue;
		}

		$body = ( null === $data ) ? '' : (string) wp_json_encode( $data, JSON_UNESCAPED_SLASHES );

		// Set status code
		http_response_code( $status );

		// Set response headers
		header( 'Content-Type: application/json' );
		header( 'Cache-Control: no-cache' );
		header( 'MCP-Protocol-Version: 2025-06-18' );
		header( 'X-Transport-Type: streamable-http' );

		// 204/304 responses must not carry a body or a Content-Length.
		if ( 204 !== $status && 304 !== $status ) {
			header( 'Content-Length: ' . strlen( $body ) );
		}

		// Add custom headers
		foreach ( $headers as $key => $value ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTTP headers, values are controlled by application
			header( sanitize_key( $key ) . ': ' . sanitize_text_field( $value ) );
		}

		if ( '' !== $body ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON body produced by wp_json_encode().
			echo $body;
		}

		flush();
		exit;
	}

	/**
	 * Send an error response.
	 *
	 * @param array $error Error data.
	 * @param int   $status HTTP status code.
	 */
	private function send_error_response( array $error, int $status = 400 ) {
		$this->send_response( $error, $status );
	}

	/**
	 * Handle streamable POST requests
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	private function handle_streamable_post_request( WP_REST_Request $request ) {
		try {
			// Validate streamable headers - REQUIRED for streamable transport
			if ( ! $this->validate_streamable_headers( $request ) ) {
				$this->send_error_response(
					McpErrorHandler::invalid_accept_header( 0 ),
					400
				);
				return;
			}
			
			// Validate content type - be more flexible with content-type headers
			$content_type = $request->get_header( 'content-type' );
			if ( $content_type && strpos( $content_type, 'application/json' ) === false ) {
				$this->send_error_response(
					McpErrorHandler::invalid_content_type( 0 ),
					400
				);
				return;
			}

			// Get the JSON-RPC message(s) - can be single message or array batch
			$body = $request->get_json_params();
			if ( null === $body ) {
				$this->send_error_response(
					McpErrorHandler::parse_error( 0, 'Invalid JSON in request body' ),
					400
				);
				return;
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
					$this->send_error_response( $validation_result, 400 );
					return;
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
				$this->send_response( null, 202 );
				return;
			}

			// Process requests
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

			$headers = array();

			// If this batch included initialize, assign a session ID per spec (optional for clients)
			if ( $has_initialize ) {
				if ( function_exists( 'wp_generate_uuid4' ) ) {
					$headers['Mcp-Session-Id'] = wp_generate_uuid4();
				} else {
					$headers['Mcp-Session-Id'] = bin2hex( random_bytes( 16 ) );
				}
			}

			// Send the response
			$this->send_response( $response_body, 200, $headers );
		} catch ( \Throwable $exception ) {
			// Handle any unexpected exceptions
			McpErrorHandler::log_error( 'Unexpected error in handle_streamable_post_request', array( 'exception' => $exception->getMessage() ) );
			$this->send_error_response(
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

		
		// Set CORS headers for all requests to our endpoint - allow all domains with Claude.ai specific headers
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS, HEAD' );
		header( 'Access-Control-Allow-Headers: content-type, accept, anthropic-beta, authorization, mcp-protocol-version, mcp-session-id, user-agent, cache-control, pragma' );
		header( 'Access-Control-Expose-Headers: MCP-Protocol-Version, Mcp-Session-Id' );
		header( 'Access-Control-Max-Age: 1800' );
		header( 'Access-Control-Allow-Credentials: false' );

		// Handle OPTIONS preflight request
		if ( $request->get_method() === 'OPTIONS' ) {
			http_response_code( 204 );
			exit;
		}

		return $served;
	}
}

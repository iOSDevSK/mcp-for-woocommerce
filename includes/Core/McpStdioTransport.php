<?php
declare(strict_types=1);


namespace McpForWoo\Core;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class McpStdioTransport
 *
 * Registers REST API routes for the Model Context Protocol (MCP) STDIO transport.
 * Uses WordPress-style responses for STDIO transport via mcp-wordpress-remote.
 */
class McpStdioTransport extends McpTransportBase {

	/**
	 * Initialize the class and register routes
	 *
	 * @param WpMcp $mcp The WordPress MCP instance.
	 */
	public function __construct( WpMcp $mcp ) {
		parent::__construct( $mcp );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
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
			'/wpmcp',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_request' ),
				// Intentionally public: every tool returns only storefront data a
				// visitor can already see, nothing is written, and no caller is ever
				// signed in. The route exists only while "Enable MCP" is on.
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle all MCP requests
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_request( WP_REST_Request $request ): WP_Error|WP_REST_Response {
		$message = $request->get_json_params();

		if ( empty( $message ) || ! isset( $message['method'] ) ) {
			return new WP_Error(
				'invalid_request',
				'Invalid request: method parameter is required [DEBUG: message=' . wp_json_encode($message) . ']',
				array( 'status' => 400 )
			);
		}
		
		$method = $message['method'];
		$params = $message['params'] ?? $message; // backward compatibility with the old request format.

		// Route the request using the base class
		$result = $this->route_request( $method, $params );
		
		// Check if the result contains an error
		if ( isset( $result['error'] ) ) {
			return $this->format_error_response( $result );
		}

		return $this->format_success_response( $result );
	}

	/**
	 * Create a method not found error (WordPress format)
	 *
	 * @param string $method The method that was not found.
	 * @param int    $request_id The request ID (unused in WordPress format).
	 * @return array
	 */
	protected function create_method_not_found_error( string $method, int $request_id ): array {
		return array(
			'error' => array(
				'code'    => 'invalid_method',
				'message' => 'Invalid method: ' . $method,
				'data'    => array( 'status' => 400 ),
			),
		);
	}

	/**
	 * Handle exceptions that occur during request processing (WordPress format)
	 *
	 * @param \Throwable $exception The exception.
	 * @param int        $request_id The request ID (unused in WordPress format).
	 * @return array
	 */
	protected function handle_exception( \Throwable $exception, int $request_id ): array {
		return array(
			'error' => array(
				'code'    => 'handler_error',
				'message' => 'Handler error occurred: ' . $exception->getMessage(),
				'data'    => array( 'status' => 500 ),
			),
		);
	}

	/**
	 * Format a successful response (WordPress format)
	 *
	 * @param array $result The result data.
	 * @param int   $request_id The request ID (unused in WordPress format).
	 * @return WP_REST_Response
	 */
	protected function format_success_response( array $result, int $request_id = 0 ): WP_REST_Response {
		return rest_ensure_response( $result );
	}

	/**
	 * Format an error response (WordPress format)
	 *
	 * @param array $error The error data.
	 * @param int   $request_id The request ID (unused in WordPress format).
	 * @return WP_Error
	 */
	protected function format_error_response( array $error, int $request_id = 0 ): WP_Error {
		$error_details = $error['error'] ?? $error;
		
		return new WP_Error(
			$error_details['code'] ?? 'handler_error',
			($error_details['message'] ?? 'Handler error occurred') . ' [DEBUG: ' . wp_json_encode($error_details) . ']',
			array( 'status' => $error_details['data']['status'] ?? 500 )
		);
	}
}

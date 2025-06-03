<?php //phpcs:ignore
/**
 * The WordPress MCP Streamable HTTP Transport class.
 *
 * @package WordPressMcp
 */

namespace Automattic\WordpressMcp\Core;

use Automattic\WordpressMcp\RequestMethodHandlers\InitializeHandler;
use Automattic\WordpressMcp\RequestMethodHandlers\ToolsHandler;
use Automattic\WordpressMcp\RequestMethodHandlers\ResourcesHandler;
use Automattic\WordpressMcp\RequestMethodHandlers\PromptsHandler;
use Automattic\WordpressMcp\RequestMethodHandlers\SystemHandler;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * The WordPress MCP Streamable HTTP Transport class.
 */
class McpStreamable {

	/**
	 * The request ID.
	 *
	 * @var int
	 */
	private int $request_id = 0;

	/**
	 * The initialize handler.
	 *
	 * @var InitializeHandler
	 */
	private InitializeHandler $initialize_handler;

	/**
	 * The tools handler.
	 *
	 * @var ToolsHandler
	 */
	private ToolsHandler $tools_handler;

	/**
	 * The resources handler.
	 *
	 * @var ResourcesHandler
	 */
	private ResourcesHandler $resources_handler;

	/**
	 * The prompts handler.
	 *
	 * @var PromptsHandler
	 */
	private PromptsHandler $prompts_handler;

	/**
	 * The system handler.
	 *
	 * @var SystemHandler
	 */
	private SystemHandler $system_handler;

	/**
	 * Initialize the class and register routes
	 *
	 * @param WpMcp $mcp The WordPress MCP instance.
	 */
	public function __construct( WpMcp $mcp ) {

		// Initialize handlers
		$this->initialize_handler = new InitializeHandler();
		$this->tools_handler      = new ToolsHandler( $mcp );
		$this->resources_handler  = new ResourcesHandler( $mcp );
		$this->prompts_handler    = new PromptsHandler( $mcp );
		$this->system_handler     = new SystemHandler();

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all MCP proxy routes
	 */
	public function register_routes(): void {
		// Check if MCP is enabled in settings.
		$options = get_option( 'wordpress_mcp_settings', array() );
		$enabled = isset( $options['enabled'] ) && $options['enabled'];

		// If MCP is disabled, don't register routes.
		if ( ! $enabled ) {
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

		// Check if MCP is enabled in settings.
		$options = get_option( 'wordpress_mcp_settings', array() );
		$enabled = isset( $options['enabled'] ) && $options['enabled'];

		// If MCP is disabled, deny access.
		if ( ! $enabled ) {
			return new WP_Error(
				'mcp_disabled',
				'MCP functionality is currently disabled.',
				array( 'status' => 403 )
			);
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle the HTTP request
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {
		// Log request headers for debugging.
		@ray(
			array(
				'method'  => $request->get_method(),
				'headers' => $request->get_headers(),
				'body'    => $request->get_body(),
			)
		);

		// Handle preflight requests
		if ( 'OPTIONS' === $request->get_method() ) {
			return new WP_REST_Response( null, 204 );
		}

		$method = $request->get_method();

		if ( 'POST' === $method ) {
			return $this->handle_post_request( $request );
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
			// Validate Accept header - client MUST include both content types
			$accept_header = $request->get_header( 'accept' );
			if ( ! $accept_header ||
				strpos( $accept_header, 'application/json' ) === false ||
				strpos( $accept_header, 'text/event-stream' ) === false ) {
				return new WP_REST_Response(
					McpErrorHandler::invalid_accept_header( 0 ),
					400
				);
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

			$headers = array(
				'Content-Type'                 => 'application/json',
				'Access-Control-Allow-Origin'  => '*',
				'Access-Control-Allow-Methods' => 'OPTIONS, GET, POST, PUT, PATCH, DELETE',
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
		try {
			$result = match ( $message['method'] ) {
				'initialize' => $this->initialize_handler->handle(),
				'tools/list' => $this->tools_handler->list_tools(),
				'tools/list/all' => $this->tools_handler->list_all_tools( $message ),
				'tools/call' => $this->tools_handler->call_tool( $message ),
				'resources/list' => $this->resources_handler->list_resources(),
				'resources/templates/list' => $this->resources_handler->list_resource_templates( $message ),
				'resources/read' => $this->resources_handler->read_resource( $message ),
				'resources/subscribe' => $this->resources_handler->subscribe_resource( $message ),
				'resources/unsubscribe' => $this->resources_handler->unsubscribe_resource( $message ),
				'prompts/list' => $this->prompts_handler->list_prompts(),
				'prompts/get' => $this->prompts_handler->get_prompt( $message ),
				'logging/setLevel' => $this->system_handler->set_logging_level( $message ),
				'completion/complete' => $this->system_handler->complete(),
				'roots/list' => $this->system_handler->list_roots(),
				default => array( 'error' => McpErrorHandler::method_not_found( $this->request_id, $message['method'] )['error'] ),
			};

			// Check if the result contains an error
			if ( isset( $result['error'] ) ) {
				return $this->ensure_jsonrpc_error_response( $result );
			}

			return $this->ensure_jsonrpc_response( $result );

		} catch ( \Throwable $exception ) {
			return McpErrorHandler::handle_exception( $exception, $this->request_id );
		}
	}

	/**
	 * Ensure the response is a JSON-RPC response
	 *
	 * @param array $response The response to ensure.
	 * @return array
	 */
	private function ensure_jsonrpc_response( array $response ): array {
		$response =
			array(
				'jsonrpc' => '2.0',
				'id'      => $this->request_id,
				'result'  => $response,
			);

		@ray( $response );

		return $response;
	}

	/**
	 * Ensure the error response is a JSON-RPC error response
	 *
	 * @param array $response The error response to ensure.
	 * @return array
	 */
	private function ensure_jsonrpc_error_response( array $response ): array {
		if ( isset( $response['error'] ) ) {
			return array(
				'jsonrpc' => '2.0',
				'id'      => $this->request_id,
				'error'   => $response['error'],
			);
		}

		// If it's not already a proper error response, make it one
		return McpErrorHandler::internal_error( $this->request_id, 'Invalid error response format' );
	}
}

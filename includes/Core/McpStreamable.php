<?php //phpcs:ignore
/**
 * The WordPress MCP Streamable HTTP Transport class.
 *
 * @package WordPressMcp
 */

namespace Automattic\WordpressMcp\Core;

use Automattic\WordpressMcp\Sse\McpMethodNotFound;
use Automattic\WordpressMcp\Utils\HandlePromptGet;
use Automattic\WordpressMcp\Utils\HandleToolsCall;
use stdClass;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * The WordPress MCP Streamable HTTP Transport class.
 */
class McpStreamable {
	/**
	 * The WordPress MCP instance.
	 *
	 * @var WpMcp
	 */
	private WpMcp $mcp;

	/**
	 * Initialize the class and register routes
	 *
	 * @param WpMcp $mcp The WordPress MCP instance.
	 */
	public function __construct( WpMcp $mcp ) {
		$this->mcp = $mcp;
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
				'methods'             => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
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

		// return current_user_can( 'manage_options' );
		return true;
	}

	/**
	 * Handle the HTTP request
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {
		$method = $request->get_method();

		if ( 'POST' === $method ) {
			return $this->handle_post_request( $request );
		}

		// Return 405 for GET requests as specified.
		return new WP_REST_Response( 'Method not allowed', 405 );
	}

	/**
	 * Handle POST requests
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	private function handle_post_request( $request ) {
		// Validate content type.
		$content_type = $request->get_header( 'content-type' );
		if ( 'application/json' !== $content_type ) {
			return new WP_REST_Response(
				array(
					'error' => array(
						'code'    => 'invalid_content_type',
						'message' => 'Content-Type must be application/json.',
					),
				),
				400
			);
		}

		// Get session ID from header.
		$session_id = $request->get_header( 'mcp-session-id' );

		// Get the JSON-RPC message.
		$message = $request->get_json_params();
		if ( ! $this->is_valid_jsonrpc_message( $message ) ) {
			return new WP_REST_Response(
				array(
					'error' => array(
						'code'    => 'invalid_request',
						'message' => 'Invalid JSON-RPC message format.',
					),
				),
				400
			);
		}

		// For all other requests, session ID is required.
		if ( empty( $session_id ) ) {
			$session_id = wp_generate_uuid4();
		}

		// Process the message.
		$result = $this->process_message( $message );

		// For notifications and responses, return 202 Accepted.
		// if ( ! isset( $message['id'] ) ) {
		// return new WP_REST_Response( null, 202 );
		// }

		// For requests, return the result.
		return new WP_REST_Response( $result );
	}

	/**
	 * Process a JSON-RPC message
	 *
	 * @param array $message The JSON-RPC message.
	 * @return array
	 */
	private function process_message( array $message ): array {
		@ray( $message );
		$result = match ( $message['method'] ) {
			'initialize' => $this->initialize( $message ),
			'tools/list' => $this->list_tools( $message ),
			'tools/list/all' => $this->list_all_tools( $message ),
			'tools/call' => $this->call_tool( $message ),
			'resources/list' => $this->list_resources( $message ),
			'resources/templates/list' => $this->list_resource_templates( $message ),
			'resources/read' => $this->read_resource( $message ),
			'resources/subscribe' => $this->subscribe_resource( $message ),
			'resources/unsubscribe' => $this->unsubscribe_resource( $message ),
			'prompts/list' => $this->list_prompts( $message ),
			'prompts/get' => $this->get_prompt( $message ),
			'logging/setLevel' => $this->set_logging_level( $message ),
			'completion/complete' => $this->complete( $message ),
			'roots/list' => $this->list_roots( $message ),
			default => $this->method_not_found( $message ),
		};

		return array(
			'jsonrpc' => '2.0',
			'id'      => $message['id'] ?? null,
			'result'  => $result,
		);
	}

	/**
	 * Validate if the message follows JSON-RPC format.
	 *
	 * @param mixed $message The message to validate.
	 * @return bool
	 */
	private function is_valid_jsonrpc_message( $message ): bool {
		if ( ! is_array( $message ) ) {
			return false;
		}

		// Basic JSON-RPC 2.0 message validation.
		$required_fields = array( 'jsonrpc', 'method' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $message[ $field ] ) ) {
				return false;
			}
		}

		if ( '2.0' !== $message['jsonrpc'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialize the MCP server
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function initialize( array $params ): array {
		// @todo: the name should be editable from the admin page
		$server_info = array(
			'name'    => 'WordPress MCP Server',
			'version' => '1.0.0',
		);

		// @todo: add capabilities based on your implementation
		$capabilities = array(
			'tools'      => array(
				'list' => true,
				'call' => true,
			),
			'resources'  => array(
				'list'        => true,
				'subscribe'   => true,
				'listChanged' => true,
			),
			'prompts'    => array(
				'list'        => true,
				'get'         => true,
				'listChanged' => true,
			),
			'logging'    => new stdClass(),
			'completion' => new stdClass(),
			'roots'      => array(
				'list'        => true,
				'listChanged' => true,
			),
		);

		// Send the response according to JSON-RPC 2.0 and InitializeResult schema.
		return array(
			'protocolVersion' => '2025-03-26',
			'serverInfo'      => $server_info,
			'capabilities'    => (object) $capabilities,
			'instructions'    => 'This is a WordPress MCP Server implementation that provides tools, resources, and prompts for interacting with WordPress.',
		);
	}

	/**
	 * List available tools
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function list_tools( array $params ): array {

		// Implement tool listing logic here.
		$tools = $this->mcp->get_tools();

		return array(
			'tools' => $tools,
		);
	}

	/**
	 * List all tools
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function list_all_tools( array $params ): array {
		$tools = $this->mcp->get_all_tools();

		return array( 'tools' => $tools );
	}

	/**
	 * Call a tool
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function call_tool( array $params ): array {
		if ( ! isset( $params['name'] ) ) {
			return array(
				'error' => array(
					'code'    => 'missing_parameter',
					'message' => 'Missing required parameter: name',
				),
			);
		}

		// Implement a tool calling logic here.
		$result = HandleToolsCall::run( $params );

		$response = array(
			'content' => array(
				array(
					'type' => 'text',
				),
			),
		);

		// @todo: add support for EmbeddedResource schema.ts:619.
		if ( isset( $result['type'] ) && 'image' === $result['type'] ) {
			$response['content'][0]['type'] = 'image';
			$response['content'][0]['data'] = base64_encode( $result['results'] ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			// @todo: improve this ?!.
			$response['content'][0]['mimeType'] = $result['mimeType'] ?? 'image/png';
		} else {
			$response['content'][0]['text'] = wp_json_encode( $result );
		}

		return array(
			$response,
			$params['id'],
		);
	}

	/**
	 * List resources
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function list_resources( array $params ): array {

		// Get the registered resources from the MCP instance.
		$resources = array_values( $this->mcp->get_resources() );

		return array(
			'resources' => $resources,
		);
	}

	/**
	 * List resource templates
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function list_resource_templates( array $params ): array {

		// Implement resource template listing logic here.
		$templates = array();

		return array(
			'templates' => $templates,
		);
	}

	/**
	 * Read a resource
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function read_resource( array $params ): array {
		if ( ! isset( $params['uri'] ) ) {
			return array(
				'error' => array(
					'code'    => 'missing_parameter',
					'message' => 'Missing required parameter: uri',
				),
			);
		}

		// Implement resource reading logic here.
		$uri                = $params['uri'];
		$resource_callbacks = $this->mcp->get_resource_callbacks();

		if ( ! isset( $resource_callbacks[ $uri ] ) ) {
			return array(
				'error' => array(
					'code'    => 'resource_not_found',
					'message' => 'Resource not found: ' . $uri,
				),
			);
		}

		$callback = $resource_callbacks[ $uri ];
		$content  = call_user_func( $callback, $params );

		$resource = $this->mcp->get_resources()[ $uri ];

		return array(
			'contents' => array(
				array(
					'uri'      => $uri,
					'mimeType' => $resource['mimeType'],
					'text'     => wp_json_encode( $content ),
				),
			),
		);
	}

	/**
	 * Subscribe to a resource
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function subscribe_resource( array $params ): array {
		if ( ! isset( $params['uri'] ) ) {
			return array(
				'error' => array(
					'error' => array(
						'code'    => 'missing_parameter',
						'message' => 'Missing required parameter: uri',
					),
				),
			);
		}

		// Implement resource subscription logic here.
		$uri = $params['uri'];

		return array(
			'subscriptionId' => 'sub_' . md5( $uri ),
		);
	}

	/**
	 * Unsubscribe from a resource
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function unsubscribe_resource( array $params ): array {
		if ( ! isset( $params['subscriptionId'] ) ) {
			return array(
				'error' => array(
					'code'    => 'missing_parameter',
					'message' => 'Missing required parameter: subscriptionId',
				),
			);
		}

		// @todo: Implement resource unsubscription logic here.

		return array(
			'success' => true,
		);
	}

	/**
	 * List prompt.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function list_prompts( array $params ): array {
		return array(
			'prompts' => array_values( $this->mcp->get_prompts() ),
		);
	}

	/**
	 * Get a prompt
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function get_prompt( array $params ): array {
		if ( ! isset( $params['name'] ) ) {
			return array(
				'error' => array(
					'code'    => 'missing_parameter',
					'message' => 'Missing required parameter: name',
				),
			);
		}

		// Get the prompt by name.
		$prompt_name = $params['name'];
		$prompt      = $this->mcp->get_prompt_by_name( $prompt_name );

		if ( ! $prompt ) {
			return array(
				'error' => array(
					'code'    => 'prompt_not_found',
					'message' => 'Prompt not found: ' . $prompt_name,
				),
			);
		}

		// Get the arguments for the prompt.
		$arguments = $params['arguments'] ?? array();
		$messages  = $this->mcp->get_prompt_messages( $prompt_name );

		return array(
			'result' => HandlePromptGet::run( $prompt, $messages, $arguments ),
		);
	}

	/**
	 * Set the logging level
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function set_logging_level( array $params ): array {
		if ( ! isset( $params['level'] ) ) {
			return array(
				'error' => array(
					'code'    => 'missing_parameter',
					'message' => 'Missing required parameter: level',
				),
			);
		}

		// @todo: Implement logging level setting logic here.

		return array(
			'success' => true,
		);
	}

	/**
	 * Complete a request
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function complete( array $params ): array {
		// Implement completion logic here.

		return array(
			'success' => true,
		);
	}

	/**
	 * List roots
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function list_roots( array $params ): array {
		// Implement roots listing logic here.
		$roots = array();

		return array(
			'roots' => $roots,
		);
	}

	/**
	 * Handle method not found
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	public function method_not_found( array $params ): array {
		return array(
			'error' => array(
				'code'    => 'method_not_found',
				'message' => 'Method not found: ' . $params['method'],
			),
		);
	}
}

<?php //phpcs:ignore
/**
 * Class McpWordPressRestApi
 *
 * Registers generic MCP tools for CRUD actions on any WordPress REST API endpoint.
 *
 * @package McpForWoo\Tools
 */
declare( strict_types=1 );



namespace McpForWoo\Tools;

use McpForWoo\Core\RegisterMcpTool;
use WP_REST_Request;

/**
 * Class McpWordPressRestApi
 *
 * Registers generic MCP tools for CRUD actions on any WordPress REST API endpoint.
 *
 * @package McpForWoo\Tools
 */
class McpRestApiCrud {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'mcpfowo_init', array( $this, 'register_tools' ) );
	}

	/**
	 * Register generic CRUD tools for a given REST API endpoint.
	 *
	 * Example usage: You can extend this to register tools for any custom endpoint.
	 */
	public function register_tools(): void {
		// Check if REST API CRUD tools are enabled in settings.
		$settings = get_option( 'mcpfowo_settings', array() );
		if ( empty( $settings['enable_rest_api_crud_tools'] ) ) {
			return;
		}

		// Example: Register CRUD tools for a custom endpoint '/wp/v2/example'.
		// To use for other endpoints, duplicate and adjust the route/method/name/description as needed.

		new RegisterMcpTool(
			array(
				'name'                => 'list_api_functions',
				'description'         => 'List all available WordPress REST API endpoints that support CRUD operations (Create, Read, Update, Delete). Use this first to discover what API functions are available before inspecting or calling them.',
				'type'                => 'read',
				'inputSchema'         => array(
					'type'       => 'object',
					'properties' => new \stdClass(),
					'required'   => new \stdClass(),
				),
				'callback'            => array( $this, 'get_available_tools' ),
				'permission_callback' => '__return_true',
				'annotations'         => array(
					'title'         => 'List API Functions',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'                => 'get_function_details',
				'description'         => 'Get detailed metadata for a specific WordPress REST API endpoint and HTTP method. Includes available parameters, required fields, authentication needs, and expected response structure. Use this to get the details of a specific function before calling it.',
				'type'                => 'read',
				'inputSchema'         => array(
					'type'       => 'object',
					'properties' => array(
						'route'  => array(
							'type'        => 'string',
							'description' => 'The REST API route (e.g., "/wp/v2/posts", "/wp/v2/users")',
						),
						'method' => array(
							'type'        => 'string',
							'enum'        => array( 'GET', 'POST', 'PATCH', 'DELETE' ),
							'description' => 'The HTTP method to retrieve metadata for',
						),
					),
					'required'   => array( 'route', 'method' ),
				),
				'callback'            => array( $this, 'get_tool_details' ),
				'permission_callback' => '__return_true',
				'annotations'         => array(
					'title'         => 'Get Function Details',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'                => 'run_api_function',
				'description'         => 'Execute read-only WordPress REST API functions by providing the endpoint route. Only supports GET operations for security reasons.',
				'type'                => 'read',
				'inputSchema'         => array(
					'type'       => 'object',
					'properties' => array(
						'route'  => array(
							'type'        => 'string',
							'description' => 'The REST API route (e.g., "/wp/v2/posts", "/wp/v2/users/123")',
						),
						'method' => array(
							'type'        => 'string',
							'enum'        => array( 'GET' ),
							'description' => 'The HTTP method to use: Only GET is allowed',
						),
						'data'   => array(
							'type'        => 'object',
							'description' => 'Query parameters for GET requests.',
						),
					),
					'required'   => array( 'route', 'method' ),
				),
				'callback'            => array( $this, 'handle_tool_run_request' ),
				'permission_callback' => '__return_true',
				'annotations'         => array(
					'title'           => 'Run API Function (Read Only)',
					'readOnlyHint'    => true,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);
	}

	/**
	 * Handle a REST API request (read-only).
	 *
	 * @param array $data The request data.
	 * @return array The response data.
	 */
	public function handle_tool_run_request( array $data ): array {
		$route  = $data['route'];
		$method = $data['method'];
		$params = $data['data'] ?? array();

		// Only allow GET requests for security
		if ( $method !== 'GET' ) {
			return array(
				'error' => 'Only GET (read) operations are allowed for security reasons.',
				'code'  => 'method_not_allowed',
			);
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			return array(
				'error' => 'Authentication required.',
				'code'  => 'unauthorized',
			);
		}

		$rest_request = new WP_REST_Request( $method, $route );
		$rest_request->set_query_params( $params );
		$response = rest_do_request( $rest_request );
		return $response->get_data();
	}

	/**
	 * Get all routes and methods from the WordPress REST API.
	 *
	 * @return array The routes and methods.
	 */
	public function get_available_tools(): array {
		$exact_ignore_routes       = array(
			'/',
			'/batch/v1',
		);
		$containing_ignore_strings = array(
			'oembed',
			'autosaves',
			'revisions',
			'jwt-auth',
		);
		// Get all routes and methods from the WordPress REST API.
		$routes = rest_get_server()->get_routes();
		$result = array();
		foreach ( $routes as $route => $methods ) {
			// Skip if route exactly matches any ignore route.
			if ( in_array( $route, $exact_ignore_routes, true ) ) {
				continue;
			}
			// Skip if route contains any of the ignore strings.
			foreach ( $containing_ignore_strings as $ignore_string ) {
				if ( strpos( $route, $ignore_string ) !== false ) {
					continue 2;
				}
			}
			foreach ( $methods as $the_methods ) {
				$result[] = array(
					'route'  => $route,
					'method' => key( $the_methods['methods'] ),
				);
			}
		}
		return $result;
	}

	/**
	 * Get details of a WordPress REST API tool.
	 *
	 * @param array $data The request data.
	 * @return array|null The response data.
	 */
	public function get_tool_details( array $data ): array {
		$route  = $data['route'];
		$method = $data['method'];

		$routes = rest_get_server()->get_routes();
		foreach ( $routes as $route => $methods ) {
			foreach ( $methods as $method => $args ) {
				if ( $route === $route && $method === $method ) {
					return $args;
				}
			}
		}
		return array();
	}
}

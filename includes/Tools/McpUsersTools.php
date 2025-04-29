<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class for managing MCP Users Tools functionality.
 */
class McpUsersTools {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wordpress_mcp_init', array( $this, 'register_tools' ) );
	}

	/**
	 * Register the tools.
	 */
	public function register_tools(): void {
		new RegisterMcpTool(
			array(
				'name'        => 'wp_users_search',
				'description' => 'Search and filter WordPress users with pagination',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_get_user',
				'description' => 'Get a WordPress user by ID',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users/(?P<id>[\d]+)',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_add_user',
				'description' => 'Add a new WordPress user',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users',
					'method' => 'POST',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_update_user',
				'description' => 'Update a WordPress user by ID',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_delete_user',
				'description' => 'Delete a WordPress user by ID',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
			)
		);

		// Get current user
		new RegisterMcpTool(
			array(
				'name'        => 'wp_get_current_user',
				'description' => 'Get the current logged-in user',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users/me',
					'method' => 'GET',
				),
			)
		);

		// Update current user
		new RegisterMcpTool(
			array(
				'name'        => 'wp_update_current_user',
				'description' => 'Update the current logged-in user',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users/me',
					'method' => 'PUT',
				),
			)
		);
	}
}

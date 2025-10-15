<?php

declare(strict_types=1);


namespace McpForWoo\Tools;

use McpForWoo\Core\RegisterMcpTool;

/**
 * Class for managing MCP Users Tools functionality.
 */
class McpUsersTools {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'mcpfowo_init', array( $this, 'register_tools' ) );
	}

	/**
	 * Register the tools.
	 */
	public function register_tools(): void {
		new RegisterMcpTool(
			array(
				'name'        => 'wp_users_search',
				'description' => 'Search and filter WordPress users with pagination',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'                   => '/wp/v2/users',
					'method'                  => 'GET',
					'inputSchemaReplacements' => array(
						'properties' => array(
							'has_published_posts' => array(
								'items'   => null, // this will remove the array from the schema.
								'default' => false,
							),
						),
						'required'   => array(
							'context',
						),
					),
				),
				'annotations' => array(
					'title'         => 'Search Users',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_get_user',
				'description' => 'Get a WordPress user by ID',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users/(?P<id>[\d]+)',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Get User',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		// Removed wp_add_user, wp_update_user, wp_delete_user for security reasons

		// Get current user.
		new RegisterMcpTool(
			array(
				'name'        => 'wp_get_current_user',
				'description' => 'Get the current logged-in user',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/users/me',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Get Current User',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		// Removed wp_update_current_user for security reasons
	}
}

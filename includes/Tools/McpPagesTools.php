<?php //phpcs:ignore
declare( strict_types=1 );

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class for managing MCP Pages Tools functionality.
 */
class McpPagesTools {

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
				'name'        => 'wp_pages_search',
				'description' => 'Search and filter WordPress pages with pagination',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/pages',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Search Pages',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_get_page',
				'description' => 'Get a WordPress page by ID',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/pages/(?P<id>[\d]+)',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Get Page',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_add_page',
				'description' => 'Add a new WordPress page',
				'type'        => 'create',
				'rest_alias'  => array(
					'route'                   => '/wp/v2/pages',
					'method'                  => 'POST',
					'inputSchemaReplacements' => array(
						'properties' => array(
							'title'   => array(
								'type' => 'string',
							),
							'content' => array(
								'type'        => 'string',
								'description' => 'The content of the page in a valid Guttenberg block format',
							),
							'excerpt' => array(
								'type' => 'string',
							),
							'parent'  => array(
								'type'        => 'integer',
								'description' => 'The ID of the parent page',
							),
							'order'   => array(
								'type'        => 'integer',
								'description' => 'The order of the page in the menu',
							),
						),
						'required'   => array(
							'title',
							'content',
						),
					),
				),
				'annotations' => array(
					'title'           => 'Add Page',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
			),
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_update_page',
				'description' => 'Update a WordPress page by ID',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'  => '/wp/v2/pages/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
				'annotations' => array(
					'title'           => 'Update Page',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wp_delete_page',
				'description' => 'Delete a WordPress page by ID',
				'type'        => 'delete',
				'rest_alias'  => array(
					'route'  => '/wp/v2/pages/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
				'annotations' => array(
					'title'           => 'Delete Page',
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);
	}
}

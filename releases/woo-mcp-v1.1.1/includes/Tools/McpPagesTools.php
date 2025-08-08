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

		// Removed wp_add_page, wp_update_page, wp_delete_page for security reasons
	}
}

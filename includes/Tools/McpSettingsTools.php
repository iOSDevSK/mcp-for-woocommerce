<?php //phpcs:ignore
declare( strict_types=1 );

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class for managing MCP Settings Tools functionality.
 */
class McpSettingsTools {

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
				'name'        => 'wp_get_general_settings',
				'description' => 'Get WordPress general site settings',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wp/v2/settings',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Get General Settings',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		// Removed wp_update_general_settings for security reasons
	}
}

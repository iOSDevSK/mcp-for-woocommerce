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

		new RegisterMcpTool(
			array(
				'name'        => 'wp_update_general_settings',
				'description' => 'Update WordPress general site settings',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'                   => '/wp/v2/settings',
					'method'                  => 'POST',
					'inputSchemaReplacements' => array(
						'properties' => array(
							'title'                  => array(
								'type'        => 'string',
								'description' => 'Site title',
							),
							'description'            => array(
								'type'        => 'string',
								'description' => 'Site tagline/description',
							),
							'timezone_string'        => array(
								'type'        => 'string',
								'description' => 'Site timezone',
							),
							'date_format'            => array(
								'type'        => 'string',
								'description' => 'Date format',
							),
							'time_format'            => array(
								'type'        => 'string',
								'description' => 'Time format',
							),
							'start_of_week'          => array(
								'type'        => 'integer',
								'description' => 'Start of week (0 = Sunday, 1 = Monday, etc.)',
							),
							'language'               => array(
								'type'        => 'string',
								'description' => 'Site language',
							),
							'use_smilies'            => array(
								'type'        => 'boolean',
								'description' => 'Convert emoticons to graphics',
							),
							'default_category'       => array(
								'type'        => 'integer',
								'description' => 'Default post category',
							),
							'default_post_format'    => array(
								'type'        => 'string',
								'description' => 'Default post format',
							),
							'posts_per_page'         => array(
								'type'        => 'integer',
								'description' => 'Number of posts to show per page',
							),
							'default_comment_status' => array(
								'type'        => 'string',
								'description' => 'Default comment status (open/closed)',
							),
							'default_ping_status'    => array(
								'type'        => 'string',
								'description' => 'Default ping status (open/closed)',
							),
						),
					),
				),
				'annotations' => array(
					'title'           => 'Update General Settings',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);
	}
}

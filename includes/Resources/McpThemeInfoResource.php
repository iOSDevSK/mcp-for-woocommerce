<?php
declare(strict_types=1);


namespace McpForWoo\Resources;

use McpForWoo\Core\RegisterMcpResource;
use McpForWoo\Utils\ActiveThemeInfo;

/**
 * Class ThemeInfoResource
 *
 * Resource for retrieving information about the active WordPress theme.
 * Provides detailed information about the active theme and its parent theme if applicable.
 *
 * @package McpForWoo\Resources
 */
class McpThemeInfoResource {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'mcpfowo_init', array( $this, 'register_resource' ) );
	}

	/**
	 * Register the resource.
	 *
	 * @return void
	 */
	public function register_resource(): void {
		new RegisterMcpResource(
			array(
				'uri'         => 'WordPress://theme-info',
				'name'        => 'theme-info',
				'description' => 'Provides detailed information about the active WordPress theme',
				'mimeType'    => 'application/json',
			),
			array( $this, 'get_theme_info' )
		);
	}

	/**
	 * Get information about the active theme.
	 *
	 * @param array $params Optional parameters to filter the response.
	 *
	 * @return array
	 */
	public function get_theme_info( array $params = array() ): array {
		return ActiveThemeInfo::get_theme_info( $params );
	}
}

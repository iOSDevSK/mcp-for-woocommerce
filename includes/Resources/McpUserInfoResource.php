<?php
declare(strict_types=1);


namespace McpForWoo\Resources;

use McpForWoo\Core\RegisterMcpResource;
use McpForWoo\Utils\UsersInfo;

/**
 * Class UserInfoResource
 *
 * Resource for retrieving information about WordPress users.
 * Provides detailed information about registered users and their roles.
 *
 * @package McpForWoo\Resources
 */
class McpUserInfoResource {

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
				'uri'         => 'WordPress://user-info',
				'name'        => 'user-info',
				'description' => 'Provides detailed information about registered WordPress users and their roles',
				'mimeType'    => 'application/json',
			),
			array( $this, 'get_user_info' )
		);
	}

	/**
	 * Get information about WordPress users.
	 *
	 * @param array $params Optional parameters to filter the response.
	 *
	 * @return array
	 */
	public function get_user_info( array $params = array() ): array {
		return ( new UsersInfo() )->get_user_info();
	}
}

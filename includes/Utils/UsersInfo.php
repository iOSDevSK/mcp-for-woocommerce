<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Utils;

/**
 * Class UsersInfo
 *
 * Utility class for retrieving information about WordPress users.
 * Provides detailed information about registered users and their roles.
 *
 * @package Automattic\WordpressMcp\Utils
 */
class UsersInfo {

	/**
	 * Get information about WordPress users.
	 *
	 * @return array
	 */
	public function get_user_info(): array {
		// Get all available roles.
		$wp_roles  = wp_roles();
		$all_roles = $wp_roles->get_names();

		// Get role statistics.
		$role_stats = array();
		foreach ( $all_roles as $role_slug => $role_name ) {
			$role_users               = get_users( array( 'role' => $role_slug ) );
			$role_stats[ $role_slug ] = array(
				'name'  => $role_name,
				'count' => count( $role_users ),
			);
		}

		return array(
			'role_stats' => $role_stats,
		);
	}
}

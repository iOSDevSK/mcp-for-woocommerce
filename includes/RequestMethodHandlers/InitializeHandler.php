<?php //phpcs:ignore
/**
 * Initialize method handler for MCP requests.
 *
 * @package WordPressMcp
 */

namespace Automattic\WordpressMcp\RequestMethodHandlers;

use Automattic\WordpressMcp\Core\WpMcp;
use stdClass;

/**
 * Handles the initialize MCP method.
 */
class InitializeHandler {
	/**
	 * The WordPress MCP instance.
	 *
	 * @var WpMcp
	 */
	private WpMcp $mcp;

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Handle the initialize request.
	 *
	 * @return array
	 */
	public function handle(): array {
		$site_info = array(
			'name'        => get_bloginfo( 'name' ),
			'url'         => get_bloginfo( 'url' ),
			'description' => get_bloginfo( 'description' ),
			'language'    => get_bloginfo( 'language' ),
			'charset'     => get_bloginfo( 'charset' ),
		);

		$server_info = array(
			'name'     => 'WordPress MCP Server',
			'version'  => WORDPRESS_MCP_VERSION,
			'siteInfo' => $site_info,
		);

		// @todo: add capabilities based on your implementation
		$capabilities = array(
			'tools'      => array(
				'list' => true,
				'call' => true,
			),
			'resources'  => array(
				'list'        => true,
				'subscribe'   => true,
				'listChanged' => true,
			),
			'prompts'    => array(
				'list'        => true,
				'get'         => true,
				'listChanged' => true,
			),
			'logging'    => new stdClass(),
			'completion' => new stdClass(),
			'roots'      => array(
				'list'        => true,
				'listChanged' => true,
			),
		);

		// Send the response according to JSON-RPC 2.0 and InitializeResult schema.
		return array(
			'protocolVersion' => '2025-03-26',
			'serverInfo'      => $server_info,
			'capabilities'    => (object) $capabilities,
			'instructions'    => 'This is a WordPress MCP Server implementation that provides tools, resources, and prompts for interacting with the WordPress site ' . get_bloginfo( 'name' ) . ' (' . get_bloginfo( 'url' ) . ').',
		);
	}
}

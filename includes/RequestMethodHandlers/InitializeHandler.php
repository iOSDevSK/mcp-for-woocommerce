<?php
/**
 * Initialize method handler for MCP requests.
 *
 * @package WordPressMcp
 */



namespace McpForWoo\RequestMethodHandlers;

use McpForWoo\Core\WpMcp;
use stdClass;
use Exception;

/**
 * Handles the initialize MCP method.
 */
class InitializeHandler {
	/**
	 * The tools handler for Claude.ai workaround.
	 *
	 * @var ToolsHandler|null
	 */
	private $tools_handler;

	/**
	 * Constructor.
	 *
	 * @param ToolsHandler|null $tools_handler The tools handler for Claude.ai workaround.
	 */
	public function __construct( $tools_handler = null ) {
		$this->tools_handler = $tools_handler;
	}

	/**
	 * Handle the initialize request.
	 *
	 * @param array $params The request parameters.
	 * @return array
	 */
	public function handle( array $params = [] ): array {
		$site_info = array(
			'name'        => get_bloginfo( 'name' ),
			'url'         => get_bloginfo( 'url' ),
			'description' => get_bloginfo( 'description' ),
			'language'    => get_bloginfo( 'language' ),
			'charset'     => get_bloginfo( 'charset' ),
		);

		$server_info = array(
			'name'     => 'WordPress MCP Server',
			'version'  => MCPFOWO_VERSION,
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

		// WORKAROUND: Claude.ai has a bug where it doesn't send tools/list after initialize
		// So we include the tools directly in the initialize response
		$tools_response = null;
		if ( $this->tools_handler ) {
			try {
				$tools_result = $this->tools_handler->list_tools();
				$tools_response = $tools_result['tools'] ?? null;
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				}
			}
		}

		// Use client's protocol version or default to 2024-11-05 for web compatibility
		$client_protocol_version = $params['protocolVersion'] ?? '2024-11-05';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		}
		
		// Send the response according to JSON-RPC 2.0 and InitializeResult schema.
		$response = array(
			'protocolVersion' => $client_protocol_version,
			'serverInfo'      => $server_info,
			'capabilities'    => (object) $capabilities,
			'instructions'    => 'This is a WordPress MCP Server implementation that provides tools, resources, and prompts for interacting with the WordPress site ' . get_bloginfo( 'name' ) . ' (' . get_bloginfo( 'url' ) . ').',
		);

		// WORKAROUND: Add tools directly to initialize response for Claude.ai compatibility
        if ( $tools_response ) {
            $response['tools'] = $tools_response;
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            }
            // Note: additional counts are available via tools/debug
        }

		return $response;
	}
}

<?php // phpcs:ignore

declare(strict_types=1);
namespace Automattic\WordpressMcp\Sse;

/**
 * Handle Initialize message.
 */
class McpHandleInitialize {

	/**
	 * Handle the MCP initialize message and return the server capabilities and info.
	 *
	 * @param array $message The JSON-RPC initialize message.
	 * @return void
	 */
	public static function handle( $message ) {
		@ray( array( 'initialize' => $message ) );
		$id = isset( $message['id'] ) ? $message['id'] : null;

		$result = array(
			'protocolVersion' => '2024-11-05',
			'capabilities'    => array(
				'logging'   => new \stdClass(),
				'resources' => new \stdClass(),
				'tools'     => new \stdClass(),
			),
			'serverInfo'      => array(
				'name'    => 'WordPress MCP',
				'version' => defined( 'WORDPRESS_MCP_VERSION' ) ? WORDPRESS_MCP_VERSION : '1.0.0',
			),
		);

		$response = array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result,
		);

		echo "event: message\n";
		echo 'data: ' . wp_json_encode( $response ) . "\n\n";
		flush();
	}
}

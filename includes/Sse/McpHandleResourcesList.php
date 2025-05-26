<?php // phpcs:ignore

declare(strict_types=1);
namespace Automattic\WordpressMcp\Mcp;

use Automattic\WordpressMcp\WordpressMcp;

/**
 * Handle Resources List message.
 */
class McpHandleResourcesList {

	/**
	 * Handle resources list request.
	 *
	 * @param array $message The message.
	 * @return void
	 */
	public static function handle( $message ) {
		$mcp = WordpressMcp::instance();

		$response = array(
			'jsonrpc' => '2.0',
			'id'      => $message['id'],
			'result'  => array(
				'resources' => $mcp->get_resources(),
			),
		);

		echo "event: message\n";
		echo 'data: ' . wp_json_encode( $response ) . "\n\n";
		flush();
	}
}

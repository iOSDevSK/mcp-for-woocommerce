<?php // phpcs:ignore

declare(strict_types=1);
namespace Automattic\WordpressMcp\Mcp;

use Automattic\WordpressMcp\WordpressMcp;

/**
 * Handle Tools List message.
 */
class McpHandleToolsList {

	/**
	 * Handle tools list request.
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
				'tools' => $mcp->get_tools(),
			),
		);

		echo "event: message\n";
		echo 'data: ' . wp_json_encode( $response ) . "\n\n";
		flush();
	}
}

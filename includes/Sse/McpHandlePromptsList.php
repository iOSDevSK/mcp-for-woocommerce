<?php // phpcs:ignore

declare(strict_types=1);
namespace Automattic\WordpressMcp\Sse;

/**
 * Handle Prompts List message.
 */
class McpHandlePromptsList {

	/**
	 * Handle prompts list request.
	 *
	 * @param array $message The message.
	 * @return void
	 */
	public static function handle( $message ) {
		$mcp = WPMCP();

		$response = array(
			'jsonrpc' => '2.0',
			'id'      => $message['id'],
			'result'  => array(
				'prompts' => new \stdClass(), // Empty prompts list.
			),
		);

		echo "event: message\n";
		echo 'data: ' . wp_json_encode( $response ) . "\n\n";
		flush();
	}
}

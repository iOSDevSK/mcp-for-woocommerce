<?php

namespace Automattic\WordpressMcp\Sse;

class McpMethodNotFound {

	/**
	 * Handle the method not found.
	 *
	 * @param array $message The message.
	 */
	public static function handle( $message ) {
		$response = array(
			'jsonrpc' => '2.0',
			'id'      => $message['id'],
			'error'   => array(
				'code'    => -32601,
				'message' => 'Method not found: ' . $message['method'],
			),
		);

		echo "event: message\n";
		echo 'data: ' . wp_json_encode( $response ) . "\n\n";
		flush();
	}
}

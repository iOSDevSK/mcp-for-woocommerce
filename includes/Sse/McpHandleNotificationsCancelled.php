<?php // phpcs:ignore

declare(strict_types=1);
namespace Automattic\WordpressMcp\Sse;

use Automattic\WordpressMcp\Sse\McpData;

/**
 * Handle Notifications Cancelled message.
 */
class McpHandleNotificationsCancelled {

	/**
	 * Handle notifications cancelled message.
	 *
	 * @param array $message The message.
	 * @return void
	 */
	public static function handle( $message ) {
		// Destroy the session.
		McpData::delete_session( $message['sessionId'] );
		exit();
	}
}

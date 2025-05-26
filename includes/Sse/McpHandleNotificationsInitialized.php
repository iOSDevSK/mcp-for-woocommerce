<?php // phpcs:ignore

declare(strict_types=1);
namespace Automattic\WordpressMcp\Sse;

use Automattic\WordpressMcp\Sse\McpData;

/**
 * Handle Notifications Initialized message.
 */
class McpHandleNotificationsInitialized {

	/**
	 * Handle notifications initialized message.
	 *
	 * @param array $message The message.
	 * @return void
	 */
	public static function handle( $message ) {
		McpData::set_status( $message['sessionId'], 'initialized' );
	}
}

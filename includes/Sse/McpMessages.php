<?php //phpcs:ignore
/**
 * Manages MCP messages.
 *
 * @package WordPressMcp\Utils
 */

namespace Automattic\WordpressMcp\Utils;

/**
 * MCP Messages class.
 * Set message will add the message to the session messages array.
 * Get messages will return the messages for a session.
 * Get first message will return the first message for a session and delete it from the array.
 * Delete session messages will delete all messages for a session.
 *
 * @package WordPressMcp\Utils
 */
class McpMessages {

	const TRANSIENT_NAME       = '_transient_mcp_sse_';
	const TRANSIENT_EXPIRED    = '_transient_timeout_mcp_sse_';
	const TRANSIENT_EXPIRATION = 180; // 3 minutes.

	/**
	 * Get messages for a session.
	 *
	 * @param string $session_id The session ID.
	 * @return array
	 */
	public static function get_messages( $session_id ) {
		global $wpdb;

		$transient_name    = self::TRANSIENT_NAME . $session_id;
		$transient_expired = self::TRANSIENT_EXPIRED . $session_id;

		// @todo: Probably we should not manage transients expiration here.
		$transient_expired = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $transient_expired ) );

		if ( $transient_expired < time() ) {
			self::delete_session( $session_id );
			return array();
		}

		$transient_value = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $transient_name ) );
		return maybe_unserialize( $transient_value->option_value ?? null );
	}

	/**
	 * Get the first message for a session.
	 *
	 * @param string $session_id The session ID.
	 * @return array
	 */
	public static function get_first_message( $session_id ) {
		$messages = self::get_messages( $session_id );
		if ( empty( $messages ) ) {
			return array();
		}

		// delete the message from the array.
		$message = array_shift( $messages );
		self::set_messages( $session_id, $messages );
		@ray( array( 'get first message > set messages' => $messages ) );
		return $message;
	}

	/**
	 * Set messages for a session.
	 *
	 * @param string $session_id The session ID.
	 * @param array  $messages The messages.
	 * @return bool|int
	 */
	private static function set_messages( $session_id, $messages ) {
		global $wpdb;
		@ray(
			array(
				'set messages' => $session_id,
				$messages,
			)
		);

		$transient_name = self::TRANSIENT_NAME . $session_id;
		$exists         = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
				$transient_name
			)
		);

		if ( $exists ) {
			return $wpdb->update(
				$wpdb->options,
				array(
					'option_value' => maybe_serialize( $messages ),
				),
				array( 'option_name' => $transient_name )
			);
		}

		return $wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => $transient_name,
				'option_value' => maybe_serialize( $messages ),
				'autoload'     => 'off',
			)
		);
	}

	/**
	 * Set a message for a session.
	 *
	 * @param array  $message The MCP request message.
	 * @param string $session_id The session ID.
	 * @return bool|int
	 */
	public static function set_message( array $message, string $session_id ) {

		global $wpdb;

		@ray( array( 'set message' => $message ) );

		// Expected in the array: sessionId, id, method, params.
		if ( ! isset( $session_id ) || ! isset( $message['method'] ) ) {
			// return MCP error.
			throw new \Exception( 'Invalid message format' );
		}

		$messages = self::get_messages( $session_id );
		if ( empty( $messages ) ) {
			$messages = array();
		}

		$messages[] = $message;

		// Insert or update transient expiration.
		$transient_expired_name  = self::TRANSIENT_EXPIRED . $session_id;
		$transient_expired_value = time() + self::TRANSIENT_EXPIRATION;
		$expiration              = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $transient_expired_name ) );
		if ( $expiration ) {
			$update = $wpdb->update( $wpdb->options, array( 'option_value' => $transient_expired_value ), array( 'option_name' => $transient_expired_name ) );
		} else {
			$update = $wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $transient_expired_name,
					'option_value' => $transient_expired_value,
					'autoload'     => 'off',
				)
			);
		}

		@ray( array( 'update transient expiration' => $update ) );

		return self::set_messages( $session_id, $messages );
	}

	/**
	 * Delete all messages for a session.
	 *
	 * @param string $session_id The session ID.
	 * @return void
	 */
	public static function delete_session( $session_id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->options, array( 'option_name' => self::TRANSIENT_NAME . $session_id ) );
		$wpdb->delete( $wpdb->options, array( 'option_name' => self::TRANSIENT_EXPIRED . $session_id ) );
	}

	/**
	 * Set the session.
	 *
	 * @param string $session_id The session ID.
	 * @return void
	 */
	public static function set_session( $session_id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => time() + self::TRANSIENT_EXPIRATION ),
			array( 'option_name' => self::TRANSIENT_EXPIRED . $session_id )
		);
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => true ),
			array( 'option_name' => self::TRANSIENT_NAME . $session_id )
		);
	}

	/**
	 * Get the session.
	 *
	 * @param string $session_id The session ID.
	 * @return array
	 */
	public static function get_session( $session_id ) {
		global $wpdb;
		$session = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->options} WHERE option_name = %s", self::TRANSIENT_NAME . $session_id ), ARRAY_A );
		return maybe_unserialize( $session['option_value'] );
	}
}

<?php //phpcs:ignore
/**
 * Manages MCP data.
 *
 * @package WordPressMcp\Utils
 */

namespace Automattic\WordpressMcp\Sse;

/**
 * MCP Data class.
 *
 * @package WordPressMcp\Utils
 */
class McpData {

	const TRANSIENT_NAME       = '_transient_mcp_data_';
	const TRANSIENT_EXPIRED    = '_transient_timeout_mcp_data_';
	const TRANSIENT_EXPIRATION = 300; // 5 minutes.

	/**
	 * Add a message to the data.
	 *
	 * @param string $session_id The session ID.
	 * @param array  $message The message.
	 * @return void
	 */
	public static function add_message( $session_id, $message ) {
		$data = self::get_data( $session_id );
		if ( empty( $message['sessionId'] ) ) {
			$message['sessionId'] = $session_id;
		}
		$data['messages'][] = $message;
		// Store the timestamp of the last message at the data level.
		$data['last_message_timestamp'] = time();
		self::set_data( $session_id, $data );
	}

	/**
	 * Get the first message for a session, and remove it from the data.
	 *
	 * @param string $session_id The session ID.
	 * @return array
	 */
	public static function get_first_message( $session_id ) {
		$data          = self::get_data( $session_id );
		$messages      = $data['messages'] ?? array();
		$first_message = array_shift( $messages );
		if ( $first_message ) {
			self::set_data( $session_id, array( 'messages' => $messages ) );
			return $first_message;
		}
		return array();
	}

	/**
	 * Set the status for a session.
	 *
	 * @param string $session_id The session ID.
	 * @param string $status The status. Possible values: 'initializing', 'initialized', 'cancelled'.
	 * @return void
	 */
	public static function set_status( $session_id, $status ) {
		if ( ! in_array( $status, array( 'initializing', 'initialized', 'cancelled' ), true ) ) {
			return;
		}
		$data           = self::get_data( $session_id );
		$data['status'] = $status;
		self::set_data( $session_id, $data );
	}

	/**
	 * Get the current status for a session.
	 *
	 * @param string $session_id The session ID.
	 * @return string
	 */
	public static function get_status( $session_id ) {
		$data = self::get_data( $session_id );
		return $data['status'] ?? 'initializing';
	}

	/**
	 * Get the data for a session.
	 *
	 * @param string $session_id The session ID.
	 * @return array
	 */
	private static function get_data( $session_id ) {
		global $wpdb;
		$transient_name = self::TRANSIENT_NAME . $session_id;

		$transient_value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $transient_name ) );
		return maybe_unserialize( $transient_value ?? null );
	}

	/**
	 * Set the data for a session.
	 *
	 * @param string $session_id The session ID.
	 * @param array  $data The data.
	 * @return void
	 */
	private static function set_data( $session_id, $data ) {
		global $wpdb;
		$transient_name = self::TRANSIENT_NAME . $session_id;

		$current_data = self::get_data( $session_id );

		if ( $current_data ) {
			$data = array_merge( $current_data, $data );
			$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $data ) ), array( 'option_name' => $transient_name ) );
		} else {
			$wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $transient_name,
					'option_value' => maybe_serialize( $data ),
					'autoload'     => 'off',
				)
			);
		}

		self::set_transient_expiration( $session_id );
	}

	/**
	 * Set the transient expiration.
	 *
	 * @param string $session_id The session ID.
	 * @return void
	 */
	private static function set_transient_expiration( $session_id ) {
		global $wpdb;
		$transient_expired = self::TRANSIENT_EXPIRED . $session_id;

		// Insert or update the transient expired.
		// get current transient expired.
		$current_transient_expiration_time = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $transient_expired ) );
		if ( $current_transient_expiration_time ) {
			$wpdb->update(
				$wpdb->options,
				array(
					'option_value' => time() + self::TRANSIENT_EXPIRATION,
				),
				array( 'option_name' => $transient_expired )
			);
		} else {
			$wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $transient_expired,
					'option_value' => time() + self::TRANSIENT_EXPIRATION,
					'autoload'     => 'off',
				)
			);
		}
	}

	/**
	 * Get the time elapsed since the last message was added.
	 *
	 * @param string $session_id The session ID.
	 * @return int Time elapsed in seconds, or 0 if no messages have been added.
	 */
	public static function get_time_since_last_message( $session_id ) {
		$data                   = self::get_data( $session_id );
		$last_message_timestamp = $data['last_message_timestamp'] ?? 0;

		if ( 0 === $last_message_timestamp ) {
			return 0;
		}

		return time() - $last_message_timestamp;
	}

	/**
	 * Delete a session.
	 *
	 * @param string $session_id The session ID.
	 * @return void
	 */
	public static function delete_session( $session_id ) {
		global $wpdb;
		$transient_name = self::TRANSIENT_NAME . $session_id;
		$wpdb->delete( $wpdb->options, array( 'option_name' => $transient_name ) );

		$transient_expired = self::TRANSIENT_EXPIRED . $session_id;
		$wpdb->delete( $wpdb->options, array( 'option_name' => $transient_expired ) );
	}
}

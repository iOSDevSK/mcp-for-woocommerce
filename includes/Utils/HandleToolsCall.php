<?php
declare(strict_types=1);


namespace McpForWoo\Utils;

use McpForWoo\Core\WpMcp;
use McpForWoo\Core\McpErrorHandler;
use Exception;
use WP_REST_Request;

/**
 * Handle Tools Call message.
 */
class HandleToolsCall {

	/**
	 * Handle tool call request.
	 *
	 * @param array $message The message.
	 *
	 * @return array
	 */
	public static function run( array $message ): array {
		$tool_name = $message['params']['name'] ?? $message['name'] ?? '';
		$args      = $message['params']['arguments'] ?? $message['arguments'] ?? array();

		// Get the WordPress MCP instance.
		$wpmcp = WpMcp::instance();

		// Get the tool callbacks.
		$tools_callbacks = $wpmcp->get_tools_callbacks();

		// Check if the tool exists.
		if ( ! isset( $tools_callbacks[ $tool_name ] ) ) {
			return array(
				'error' => McpErrorHandler::tool_not_found( 0, $tool_name ),
			);
		}

		// Get the tool callback.
		$tool_callback = $tools_callbacks[ $tool_name ];

		// Check permissions first.
		if ( isset( $tool_callback['permission_callback'] ) && is_callable( $tool_callback['permission_callback'] ) ) {
			$permission_result = call_user_func( $tool_callback['permission_callback'], $args );
			if ( ! $permission_result ) {
				return array(
					'error' => array(
						'code' => 'rest_forbidden',
						'message' => 'Permission denied',
						'data' => array( 'status' => 403 )
					),
				);
			}
		}

		// Execute the tool callback.
		try {
			$result = call_user_func( $tool_callback['callback'], $args );
			return $result;
		} catch ( \Exception $e ) {
			McpErrorHandler::log_error(
				'Tool execution failed',
				array(
					'tool'      => $tool_name,
					'exception' => $e->getMessage(),
				)
			);
			return array(
				'error' => McpErrorHandler::create_error_response(
					0,
					McpErrorHandler::INTERNAL_ERROR,
					'Error executing tool',
					$e->getMessage()
				),
			);
		}
	}
}

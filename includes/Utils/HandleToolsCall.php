<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Utils;

use Automattic\WordpressMcp\Core\WpMcp;
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
				'jsonrpc' => '2.0',
				'id'      => $message['id'] ?? null,
				'error'   => array(
					'code'    => -32601,
					'message' => 'Method not found: ' . $tool_name,
				),
			);
		}

		// Get the tool callback.
		$tool_callback = $tools_callbacks[ $tool_name ];

		// Check permissions.
		if ( isset( $tool_callback['permissions_callback'] ) && is_callable( $tool_callback['permissions_callback'] ) ) {
			$permission_result = call_user_func( $tool_callback['permissions_callback'], $args );

			if ( ! $permission_result ) {
				return array(
					'jsonrpc' => '2.0',
					'id'      => $message['id'],
					'error'   => array(
						'code'    => -32000,
						'message' => 'Permission denied for tool: ' . $tool_name,
					),
				);
			}
		}

		// Handle REST API alias if present.
		if ( isset( $tool_callback['rest_alias'] ) ) {
			try {
				$route = $tool_callback['rest_alias']['route'];
				// Replace route parameters with actual values.
				foreach ( $args as $key => $value ) {
					$pattern = '(?P<' . $key . '>[\\d]+)';
					$route   = str_replace( $pattern, is_array( $value ) ? json_encode( $value ) : (string) $value, $route );
				}
				$request = new WP_REST_Request( $tool_callback['rest_alias']['method'], $route );

				// Add default parameters for user-related requests.
				if ( strpos( $route, '/wp/v2/users' ) !== false ) {
					$request->set_param( 'context', 'view' );
					$request->set_param( 'orderby', 'id' );
					$request->set_param( 'who', 'authors' );
				}

				foreach ( $args as $key => $value ) {
					$request->set_param( $key, $value );
				}

				$rest_response = rest_do_request( $request );

				if ( $rest_response->is_error() ) {
					// Handle REST API error.
					$response = array(
						'jsonrpc' => '2.0',
						'id'      => $message['id'] ?? null,
						'error'   => array(
							'code'    => -32000,
							'message' => 'REST API error occurred. ' . $rest_response->as_error()->get_error_message(),
						),
					);
				} else {
					$response = array(
						'jsonrpc' => '2.0',
						'id'      => $message['id'] ?? null,
						'result'  => $rest_response->get_data(),
					);
				}
			} catch ( Exception $e ) {
				$response = array(
					'jsonrpc' => '2.0',
					'id'      => $message['id'],
					'error'   => array(
						'code'    => -32000,
						'message' => 'Error executing REST API: ' . $e->getMessage(),
					),
				);
			}
		} else {
			// Execute the tool callback.
			try {
				$result = call_user_func( $tool_callback['callback'], $args );

				$response = array(
					'jsonrpc' => '2.0',
					'id'      => $message['id'],
					'result'  => $result,
				);
			} catch ( Exception $e ) {
				$response = array(
					'jsonrpc' => '2.0',
					'id'      => $message['id'],
					'error'   => array(
						'code'    => -32000,
						'message' => 'Error executing tool: ' . $e->getMessage(),
					),
				);
			}
		}

		return $response;
	}
}

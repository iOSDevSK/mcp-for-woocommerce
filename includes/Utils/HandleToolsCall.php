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
				'error' => array(
					'code'    => -32601,
					'message' => 'Method not found: ' . $tool_name,
				),
			);
		}

		// Get the tool callback.
		$tool_callback = $tools_callbacks[ $tool_name ];

		// Handle REST API alias if present.
		if ( isset( $tool_callback['rest_alias'] ) ) {
			try {
				$route = $tool_callback['rest_alias']['route'];
				// Replace route parameters with actual values.
				foreach ( $args as $key => $value ) {
					$pattern = '(?P<' . $key . '>[\\d]+)';
					$route   = str_replace( $pattern, is_array( $value ) ? json_encode( $value ) : (string) $value, $route );
				}

				$headers = null;
				$body    = null;
				// Run the pre-callback if present.
				if ( isset( $tool_callback['rest_alias']['preCallback'] ) && is_callable( $tool_callback['rest_alias']['preCallback'] ) ) {
					$new_params = call_user_func( $tool_callback['rest_alias']['preCallback'], $args );
					$args       = $new_params['args'];
					$headers    = $new_params['headers'];
					$body       = $new_params['body'];
				}

				$request = new WP_REST_Request( $tool_callback['rest_alias']['method'], $route );

				// Handle headers if present.
				if ( $headers ) {
					foreach ( $headers as $header_name => $header_value ) {
						$request->add_header( $header_name, $header_value[0] );
					}
				}

				// Handle body if present.
				if ( $body ) {
					$request->set_body( $body );
				}

				// Set remaining parameters.
				foreach ( $args as $key => $value ) {
					$request->set_param( $key, $value );
				}

				if ( isset( $tool_callback['permission_callback'] ) && is_callable( $tool_callback['permission_callback'] ) ) {
					$permission_result = call_user_func( $tool_callback['permission_callback'], $request );

					if ( ! $permission_result ) {
						return array(
							'error' => array(
								'code'    => -32000,
								'message' => 'Permission denied for tool: ' . $tool_name,
							),
						);
					}
				}

				$rest_response = rest_do_request( $request );

				if ( $rest_response->is_error() ) {
					// Handle REST API error.
					$response = array(
						'error' => array(
							'code'    => -32000,
							'message' => $rest_response->as_error()->get_error_message(),
						),
					);
				} else {
					$response = $rest_response->get_data();
				}
			} catch ( Exception $e ) {
				$response = array(
					'error' => array(
						'code'    => -32000,
						'message' => 'Error executing REST API: ' . $e->getMessage(),
					),
				);
			}
		} else {
			if ( isset( $tool_callback['permission_callback'] ) && is_callable( $tool_callback['permission_callback'] ) ) {
				$permission_result = call_user_func( $tool_callback['permission_callback'], $args );
				if ( ! $permission_result ) {
					return array(
						'error' => array(
							'code'    => -32000,
							'message' => 'Permission denied for tool: ' . $tool_name,
						),
					);
				}
			}

			// Execute the tool callback.
			try {
				return call_user_func( $tool_callback['callback'], $args );
			} catch ( Exception $e ) {
				$response = array(
					'error' => array(
						'code'    => -32000,
						'message' => 'Error executing tool: ' . $e->getMessage(),
					),
				);
			}
		}

		return $response;
	}
}

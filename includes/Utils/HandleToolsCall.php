<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Utils;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Core\McpErrorHandler;
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
		$tool_name = $message['name'] ?? '';
		$args      = $message['arguments'] ?? array();

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

		// Handle REST API alias if present.
		if ( isset( $tool_callback['rest_alias'] ) ) {
			try {
				$rest_alias = $tool_callback['rest_alias'];
				$route      = $rest_alias['route'];
				$method     = $rest_alias['method'];

				$request = new \WP_REST_Request( $method, $route );

				// Set the arguments as query parameters or body parameters based on method.
				if ( in_array( $method, array( 'GET', 'DELETE' ), true ) ) {
					// For GET and DELETE, use query parameters.
					foreach ( $args as $key => $value ) {
						$request->set_query_params( array_merge( $request->get_query_params(), array( $key => $value ) ) );
					}
				} else {
					// For POST, PUT, PATCH, use body parameters.
					foreach ( $args as $key => $value ) {
						$request->set_param( $key, $value );
					}
				}

				$rest_response = rest_do_request( $request );

				if ( $rest_response->is_error() ) {
					// Handle REST API error.
					return array(
						'error' => McpErrorHandler::create_error_response(
							0,
							McpErrorHandler::REST_API_ERROR,
							'REST API error occurred',
							$rest_response->as_error()->get_error_message()
						),
					);
				} else {
					return $rest_response->get_data();
				}
			} catch ( \Exception $e ) {
				McpErrorHandler::log_error(
					'REST API tool execution failed',
					array(
						'tool'      => $tool_name,
						'exception' => $e->getMessage(),
					)
				);
				return array(
					'error' => McpErrorHandler::create_error_response(
						0,
						McpErrorHandler::REST_API_ERROR,
						'Error executing REST API',
						$e->getMessage()
					),
				);
			}
		} else {
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
}

<?php //phpcs:ignore
/**
 * Tools method handlers for MCP requests.
 *
 * @package WordPressMcp
 */

namespace Automattic\WordpressMcp\RequestMethodHandlers;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Core\McpErrorHandler;
use Automattic\WordpressMcp\Utils\HandleToolsCall;

/**
 * Handles tools-related MCP methods.
 */
class ToolsHandler {
	/**
	 * The WordPress MCP instance.
	 *
	 * @var WpMcp
	 */
	private WpMcp $mcp;

	/**
	 * Constructor.
	 *
	 * @param WpMcp $mcp The WordPress MCP instance.
	 */
	public function __construct( WpMcp $mcp ) {
		$this->mcp = $mcp;
	}

	/**
	 * Handle the tools/list request.
	 * Optimized for fast response to prevent Claude.ai timeouts.
	 *
	 * @return array
	 */
	public function list_tools(): array {
		// Add timeout and memory limit increase for complex tool loading
		if ( ! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 120 ); // 2 minutes
			@ini_set( 'memory_limit', '512M' );
		}
		
		try {
			$tools = $this->mcp->get_tools();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			}
			
			return array(
				'tools' => array_values( $tools ),
			);
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			}
			// Return empty tools list instead of failing completely
			return array(
				'tools' => array(),
			);
		}
	}

	/**
	 * Handle the tools/list/all request.
	 *
	 * Return ALL tools, including those disabled by settings, with reasons.
	 * This is useful for debugging when clients report "tools disabled".
	 *
	 * @param array $params Request parameters.
	 * @return array
	 */
	public function list_all_tools( array $params ): array {
		$tools = method_exists( $this->mcp, 'get_all_tools' )
			? $this->mcp->get_all_tools()
			: $this->mcp->get_tools();

		return array(
			'tools' => array_values( $tools ),
		);
	}

	/**
	 * Handle the tools/call request.
	 *
	 * @param array $message Request message.
	 * @return array
	 */
	public function call_tool( array $message ): array {
		// Handle both direct params and nested params structure.
		$request_params = $message['params'] ?? $message;

		if ( ! isset( $request_params['name'] ) ) {
			return array(
				'error' => McpErrorHandler::missing_parameter( 0, 'name' )['error'],
			);
		}

		// Clean parameters arguments.
		if ( ! empty( $request_params['arguments'] ) ) {
			foreach ( $request_params['arguments'] as $key => $value ) {
				if ( empty( $value ) || 'null' === $value ) {
					unset( $request_params['arguments'][ $key ] );
				}
			}
		}

		try {
			// Implement a tool calling logic here.
			$result = HandleToolsCall::run( $request_params );

			// Check if the result contains an error
			if ( isset( $result['error'] ) ) {
				return $result; // Return error directly
			}

			$response = array(
				'content' => array(
					array(
						'type' => 'text',
					),
				),
			);

			// @todo: add support for EmbeddedResource schema.ts:619.
			if ( isset( $result['type'] ) && 'image' === $result['type'] ) {
				$response['content'][0]['type'] = 'image';
				$response['content'][0]['data'] = base64_encode( $result['results'] ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

				// @todo: improve this ?!.
				$response['content'][0]['mimeType'] = $result['mimeType'] ?? 'image/png';
			} else {
				$response['content'][0]['text'] = wp_json_encode( $result );
			}

			return $response;

		} catch ( \Throwable $exception ) {
			McpErrorHandler::log_error(
				'Error calling tool',
				array(
					'tool'      => $request_params['name'],
					'exception' => $exception->getMessage(),
				)
			);
			return array(
				'error' => McpErrorHandler::internal_error( 0, 'Failed to execute tool' )['error'],
			);
		}
	}

	/**
	 * Debug method that returns a snapshot of tool availability and settings.
	 * Useful to diagnose why Claude shows tools disabled.
	 *
	 * @return array
	 */
	public function debug_tools_state(): array {
		try {
			$settings = function_exists('get_option') ? (array) get_option('wordpress_mcp_settings', array()) : array();
			$jwt_required = function_exists('get_option') ? (bool) get_option('wordpress_mcp_jwt_required', true) : true;
			$active_tools = $this->mcp->get_tools();
			$all_tools = method_exists($this->mcp, 'get_all_tools') ? $this->mcp->get_all_tools() : $active_tools;

			return array(
				'settings' => array(
					'enabled' => !empty($settings['enabled']),
					'enable_rest_api_crud_tools' => !empty($settings['enable_rest_api_crud_tools']),
					'features_adapter_enabled' => !empty($settings['features_adapter_enabled'] ?? false),
					'jwt_required' => $jwt_required,
				),
				'counts' => array(
					'active' => count($active_tools),
					'all' => count($all_tools),
				),
				'activeTools' => $active_tools,
				'allTools' => $all_tools,
			);
		} catch (\Throwable $e) {
			return array('error' => 'debug_tools_state failed: ' . $e->getMessage());
		}
	}
}


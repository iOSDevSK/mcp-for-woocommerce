<?php
/**
 * Tools method handlers for MCP requests.
 *
 * @package WordPressMcp
 */



namespace McpForWoo\RequestMethodHandlers;

use McpForWoo\Core\WpMcp;
use McpForWoo\Core\McpErrorHandler;
use McpForWoo\Utils\HandleToolsCall;

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
		try {
			$tools = $this->mcp->get_tools();

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
	 * Returns every tool, including the ones the site owner switched off, so the
	 * settings screen can list them with their on/off state. Only an administrator
	 * signed in to the dashboard can call it.
	 *
	 * @param array $params Request parameters.
	 * @return array
	 */
	public function list_all_tools( array $params ): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'error' => array(
					'code'    => 'rest_forbidden',
					'message' => 'You do not have permission to list all tools.',
					'data'    => array( 'status' => 403 ),
				),
			);
		}

		$tools = $this->mcp->get_all_tools();

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

			// A tool that reports a problem as plain text ("Product not found") is a
			// tool-level error: per MCP it goes back as a result flagged isError.
			if ( isset( $result['error'] ) && is_string( $result['error'] ) ) {
				return array(
					'content' => array(
						array(
							'type' => 'text',
							'text' => $result['error'],
						),
					),
					'isError' => true,
				);
			}

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
				$response['content'][0]['data'] = base64_encode( $result['results'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- MCP image content must be base64 per the protocol.

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
}

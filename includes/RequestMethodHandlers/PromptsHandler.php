<?php //phpcs:ignore
/**
 * Prompts method handlers for MCP requests.
 *
 * @package WordPressMcp
 */

namespace Automattic\WordpressMcp\RequestMethodHandlers;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Core\McpErrorHandler;
use Automattic\WordpressMcp\Utils\HandlePromptGet;

/**
 * Handles prompts-related MCP methods.
 */
class PromptsHandler {
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
	 * Check if user has permission to access prompts.
	 *
	 * @return array|null Returns error array if permission denied, null if allowed.
	 */
	private function check_permission(): ?array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'error' => array(
					'code'    => 'rest_forbidden',
					'message' => 'You do not have permission to access prompts.',
					'data'    => array( 'status' => 403 ),
				),
			);
		}
		return null;
	}

	/**
	 * Handle the prompts/list request.
	 *
	 * @return array
	 */
	public function list_prompts(): array {
		$permission_error = $this->check_permission();
		if ( $permission_error ) {
			return $permission_error;
		}

		return array(
			'prompts' => array_values( $this->mcp->get_prompts() ),
		);
	}

	/**
	 * Handle the prompts/get request.
	 *
	 * @param array $params Request parameters.
	 * @return array
	 */
	public function get_prompt( array $params ): array {
		$permission_error = $this->check_permission();
		if ( $permission_error ) {
			return $permission_error;
		}

		// Handle both direct params and nested params structure.
		$request_params = $params['params'] ?? $params;

		if ( ! isset( $request_params['name'] ) ) {
			return array(
				'error' => McpErrorHandler::missing_parameter( 0, 'name' )['error'],
			);
		}

		// Get the prompt by name.
		$prompt_name = $request_params['name'];
		$prompt      = $this->mcp->get_prompt_by_name( $prompt_name );

		if ( ! $prompt ) {
			return array(
				'error' => McpErrorHandler::prompt_not_found( 0, $prompt_name )['error'],
			);
		}

		// Get the arguments for the prompt.
		$arguments = $request_params['arguments'] ?? array();
		$messages  = $this->mcp->get_prompt_messages( $prompt_name );

		return array(
			'result' => HandlePromptGet::run( $prompt, $messages, $arguments ),
		);
	}
}

<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Prompts;

use Automattic\WordpressMcp\Core\RegisterMcpPrompt;

/**
 * Class McpAnalyzeSales
 *
 * Prompt for analyzing WooCommerce sales data.
 * Provides insights on sales performance within a specified time period.
 *
 * @package Automattic\WordpressMcp\Prompts
 */
class McpAnalyzeSales {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wordpress_mcp_init', array( $this, 'register_prompt' ) );
	}

	/**
	 * Register the prompt.
	 *
	 * @return void
	 */
	public function register_prompt(): void {
		new RegisterMcpPrompt(
			array(
				'name'        => 'analyze-sales',
				'description' => 'Analyze WooCommerce sales data',
				'arguments'   => array(
					array(
						'name'        => 'time_span',
						'description' => 'The time period to analyze (e.g., last_7_days, last_30_days, last_month, last_quarter, last_year)',
						'required'    => true,
						'type'        => 'string',
					),
				),
			),
			$this->messages()
		);
	}

	/**
	 * Get the messages for the prompt.
	 *
	 * @return array
	 */
	public function messages(): array {
		return array(
			array(
				'role'    => 'user',
				'content' => array(
					'type' => 'text',
					'text' => 'Analyze the WooCommerce sales data for the time period: {{time_span}}. Include total sales, average order value, top-selling products, and sales trends.',
				),
			),
		);
	}
}

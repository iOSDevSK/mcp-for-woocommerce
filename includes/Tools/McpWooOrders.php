<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooOrders
 *
 * Provides WooCommerce-specific tools related to orders for the WordPress MCP plugin.
 * Only registers tools if WooCommerce is active.
 */
class McpWooOrders {

	/**
	 * Constructor for McpWooOrders.
	 */
	public function __construct() {
		add_action( 'wordpress_mcp_init', array( $this, 'register_tools' ) );
	}

	/**
	 * Registers WooCommerce-specific tools for orders if WooCommerce is active.
	 *
	 * @return void
	 */
	public function register_tools(): void {
		// Only register tools if WooCommerce is active.
		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		// Orders search.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_orders_search',
				'description' => 'Get a list of WooCommerce orders',
				'rest_alias'  => array(
					'route'  => '/wc/v3/orders',
					'method' => 'GET',
				),
			)
		);

		// Reports tools
		new RegisterMcpTool(
			array(
				'name'        => 'wc_reports_coupons_totals',
				'description' => 'Get WooCommerce coupons totals report',
				'rest_alias'  => array(
					'route'  => '/wc/v3/reports/coupons/totals',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_reports_customers_totals',
				'description' => 'Get WooCommerce customers totals report',
				'rest_alias'  => array(
					'route'  => '/wc/v3/reports/customers/totals',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_reports_orders_totals',
				'description' => 'Get WooCommerce orders totals report',
				'rest_alias'  => array(
					'route'  => '/wc/v3/reports/orders/totals',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_reports_products_totals',
				'description' => 'Get WooCommerce products totals report',
				'rest_alias'  => array(
					'route'  => '/wc/v3/reports/products/totals',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_reports_reviews_totals',
				'description' => 'Get WooCommerce reviews totals report',
				'rest_alias'  => array(
					'route'  => '/wc/v3/reports/reviews/totals',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_reports_sales',
				'description' => 'Get WooCommerce sales report',
				'rest_alias'  => array(
					'route'  => '/wc/v3/reports/sales',
					'method' => 'GET',
				),
			)
		);
	}

	/**
	 * Checks if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	private function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}
}

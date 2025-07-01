<?php //phpcs:ignore
declare( strict_types=1 );

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooProducts
 *
 * Provides WooCommerce-specific readonly tools for products.
 * Only registers tools if WooCommerce is active.
 */
class McpWooProducts {

	/**
	 * Constructor for McpWooProducts.
	 */
	public function __construct() {
		add_action( 'wordpress_mcp_init', array( $this, 'register_tools' ) );
	}

	/**
	 * Registers WooCommerce-specific readonly tools for products if WooCommerce is active.
	 *
	 * @return void
	 */
	public function register_tools(): void {
		// Only register tools if WooCommerce is active.
		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		// Products - readonly only
		new RegisterMcpTool(
			array(
				'name'        => 'wc_products_search',
				'description' => 'Search and filter WooCommerce products with pagination',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Search Products',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_get_product',
				'description' => 'Get a WooCommerce product by ID',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/(?P<id>[\d]+)',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Get WooCommerce Product',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		// Product Variations - readonly
		new RegisterMcpTool(
			array(
				'name'        => 'wc_get_product_variations',
				'description' => 'Get all variations for a variable WooCommerce product',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/(?P<product_id>[\d]+)/variations',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Get Product Variations',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_get_product_variation',
				'description' => 'Get a specific product variation by ID',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/(?P<product_id>[\d]+)/variations/(?P<id>[\d]+)',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'Get Product Variation',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
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
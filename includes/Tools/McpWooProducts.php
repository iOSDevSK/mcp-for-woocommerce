<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooProducts
 *
 * Provides WooCommerce-specific tools for the WordPress MCP plugin.
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
	 * Registers WooCommerce-specific tools if WooCommerce is active.
	 *
	 * @return void
	 */
	public function register_tools(): void {
		// Only register tools if WooCommerce is active.
		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		// Products.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_products_search',
				'description' => 'Search and filter WooCommerce products with pagination',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_get_product',
				'description' => 'Get a WooCommerce product by ID',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/(?P<id>[\d]+)',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product',
				'description' => 'Add a new WooCommerce product',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products',
					'method' => 'POST',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product',
				'description' => 'Update a WooCommerce product by ID',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_delete_product',
				'description' => 'Delete a WooCommerce product by ID',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
			)
		);

		// Product Categories.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_list_product_categories',
				'description' => 'List all WooCommerce product categories',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/categories',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product_category',
				'description' => 'Add a new WooCommerce product category',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/categories',
					'method' => 'POST',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product_category',
				'description' => 'Update a WooCommerce product category',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/categories/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_delete_product_category',
				'description' => 'Delete a WooCommerce product category',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/categories/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
			)
		);

		// Product Tags.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_list_product_tags',
				'description' => 'List all WooCommerce product tags',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/tags',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product_tag',
				'description' => 'Add a new WooCommerce product tag',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/tags',
					'method' => 'POST',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product_tag',
				'description' => 'Update a WooCommerce product tag',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/tags/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_delete_product_tag',
				'description' => 'Delete a WooCommerce product tag',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/tags/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
			)
		);

		// Product Brands.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_list_product_brands',
				'description' => 'List all WooCommerce product brands',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/brands',
					'method' => 'GET',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product_brand',
				'description' => 'Add a new WooCommerce product brand',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/brands',
					'method' => 'POST',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product_brand',
				'description' => 'Update a WooCommerce product brand',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/brands/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_delete_product_brand',
				'description' => 'Delete a WooCommerce product brand',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/brands/(?P<id>[\d]+)',
					'method' => 'DELETE',
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

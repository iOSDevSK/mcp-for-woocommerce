<?php //phpcs:ignore
declare( strict_types=1 );

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

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product',
				'description' => 'Add a new WooCommerce product',
				'type'        => 'create',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products',
					'method' => 'POST',
				),
				'annotations' => array(
					'title'           => 'Add WooCommerce Product',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product',
				'description' => 'Update a WooCommerce product by ID',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
				'annotations' => array(
					'title'           => 'Update WooCommerce Product',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_delete_product',
				'description' => 'Delete a WooCommerce product by ID',
				'type'        => 'delete',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
				'annotations' => array(
					'title'           => 'Delete WooCommerce Product',
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		// Product Categories.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_list_product_categories',
				'description' => 'List all WooCommerce product categories',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/categories',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'List WooCommerce Product Categories',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product_category',
				'description' => 'Add a new WooCommerce product category',
				'type'        => 'create',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/categories',
					'method' => 'POST',
				),
				'annotations' => array(
					'title'           => 'Add WooCommerce Product Category',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product_category',
				'description' => 'Update a WooCommerce product category',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/categories/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
				'annotations' => array(
					'title'           => 'Update WooCommerce Product Category',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_delete_product_category',
				'description' => 'Delete a WooCommerce product category',
				'type'        => 'delete',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/categories/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
				'annotations' => array(
					'title'           => 'Delete WooCommerce Product Category',
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		// Product Tags.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_list_product_tags',
				'description' => 'List all WooCommerce product tags',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/tags',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'List WooCommerce Product Tags',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product_tag',
				'description' => 'Add a new WooCommerce product tag',
				'type'        => 'create',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/tags',
					'method' => 'POST',
				),
				'annotations' => array(
					'title'           => 'Add WooCommerce Product Tag',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product_tag',
				'description' => 'Update a WooCommerce product tag',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/tags/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
				'annotations' => array(
					'title'           => 'Update WooCommerce Product Tag',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_delete_product_tag',
				'description' => 'Delete a WooCommerce product tag',
				'type'        => 'delete',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/tags/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
				'annotations' => array(
					'title'           => 'Delete WooCommerce Product Tag',
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		// Product Brands.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_list_product_brands',
				'description' => 'List all WooCommerce product brands',
				'type'        => 'read',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/brands',
					'method' => 'GET',
				),
				'annotations' => array(
					'title'         => 'List WooCommerce Product Brands',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product_brand',
				'description' => 'Add a new WooCommerce product brand',
				'type'        => 'create',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/brands',
					'method' => 'POST',
				),
				'annotations' => array(
					'title'           => 'Add WooCommerce Product Brand',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product_brand',
				'description' => 'Update a WooCommerce product brand',
				'type'        => 'update',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/brands/(?P<id>[\d]+)',
					'method' => 'PUT',
				),
				'annotations' => array(
					'title'           => 'Update WooCommerce Product Brand',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_delete_product_brand',
				'description' => 'Delete a WooCommerce product brand',
				'type'        => 'delete',
				'rest_alias'  => array(
					'route'  => '/wc/v3/products/brands/(?P<id>[\d]+)',
					'method' => 'DELETE',
				),
				'annotations' => array(
					'title'           => 'Delete WooCommerce Product Brand',
					'readOnlyHint'    => false,
					'destructiveHint' => true,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
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

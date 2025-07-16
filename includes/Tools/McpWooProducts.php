<?php //phpcs:ignore
declare( strict_types=1 );

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

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

		// Products with enhanced permalink support.
		new RegisterMcpTool(
			array(
				'name'        => 'wc_products_search',
				'description' => 'Search and filter WooCommerce products with pagination (includes product permalinks)',
				'type'        => 'read',
				'callback'    => array( $this, 'enhanced_products_search' ),
				'permission_callback' => array( $this, 'check_woocommerce_permissions' ),
				'annotations' => array(
					'title'         => 'Search Products',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'context' => array(
							'type'        => 'string',
							'description' => 'Scope under which the request is made',
							'enum'        => array( 'view', 'edit' ),
							'default'     => 'view',
						),
						'page' => array(
							'type'        => 'integer',
							'description' => 'Current page of the collection',
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page' => array(
							'type'        => 'integer',
							'description' => 'Maximum number of items to be returned in result set',
							'default'     => 10,
							'minimum'     => 1,
							'maximum'     => 100,
						),
						'search' => array(
							'type'        => 'string',
							'description' => 'Limit results to those matching a string',
						),
						'after' => array(
							'type'        => 'string',
							'description' => 'Limit response to products published after a given ISO8601 compliant date',
						),
						'before' => array(
							'type'        => 'string',
							'description' => 'Limit response to products published before a given ISO8601 compliant date',
						),
						'exclude' => array(
							'type'        => 'array',
							'description' => 'Ensure result set excludes specific IDs',
							'items'       => array( 'type' => 'integer' ),
						),
						'include' => array(
							'type'        => 'array',
							'description' => 'Limit result set to specific IDs',
							'items'       => array( 'type' => 'integer' ),
						),
						'offset' => array(
							'type'        => 'integer',
							'description' => 'Offset the result set by a specific number of items',
						),
						'order' => array(
							'type'        => 'string',
							'description' => 'Order sort attribute ascending or descending',
							'enum'        => array( 'asc', 'desc' ),
							'default'     => 'desc',
						),
						'orderby' => array(
							'type'        => 'string',
							'description' => 'Sort collection by product attribute',
							'enum'        => array( 'date', 'id', 'include', 'title', 'slug', 'price', 'popularity', 'rating' ),
							'default'     => 'date',
						),
						'parent' => array(
							'type'        => 'array',
							'description' => 'Limit result set to those of particular parent IDs',
							'items'       => array( 'type' => 'integer' ),
						),
						'parent_exclude' => array(
							'type'        => 'array',
							'description' => 'Limit result set to all items except those of a particular parent ID',
							'items'       => array( 'type' => 'integer' ),
						),
						'slug' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a specific slug',
						),
						'status' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a specific status',
							'enum'        => array( 'any', 'draft', 'pending', 'private', 'publish' ),
							'default'     => 'any',
						),
						'type' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a specific type',
							'enum'        => array( 'simple', 'grouped', 'external', 'variable' ),
						),
						'sku' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a specific SKU',
						),
						'featured' => array(
							'type'        => 'boolean',
							'description' => 'Limit result set to featured products',
						),
						'category' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products assigned to a specific category ID',
						),
						'tag' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products assigned to a specific tag ID',
						),
						'shipping_class' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products assigned to a specific shipping class ID',
						),
						'attribute' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a specific attribute',
						),
						'attribute_term' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a specific attribute term ID (required when filtering by attribute)',
						),
						'tax_class' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a specific tax class',
							'enum'        => array( 'standard', 'reduced-rate', 'zero-rate' ),
						),
						'in_stock' => array(
							'type'        => 'boolean',
							'description' => 'Limit result set to products in stock or out of stock',
						),
						'on_sale' => array(
							'type'        => 'boolean',
							'description' => 'Limit result set to products on sale',
						),
						'min_price' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a price greater than or equal to this value',
						),
						'max_price' => array(
							'type'        => 'string',
							'description' => 'Limit result set to products with a price less than or equal to this value',
						),
					),
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_get_product',
				'description' => 'Get a WooCommerce product by ID (includes product permalink)',
				'type'        => 'read',
				'callback'    => array( $this, 'enhanced_get_product' ),
				'permission_callback' => array( $this, 'check_woocommerce_permissions' ),
				'annotations' => array(
					'title'         => 'Get WooCommerce Product',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
				),
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => 'Unique identifier for the product',
							'required'    => true,
						),
						'context' => array(
							'type'        => 'string',
							'description' => 'Scope under which the request is made',
							'enum'        => array( 'view', 'edit' ),
							'default'     => 'view',
						),
					),
					'required' => array( 'id' ),
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_add_product',
				'description' => 'Add a new WooCommerce product (returns product with permalink)',
				'type'        => 'create',
				'callback'    => array( $this, 'enhanced_add_product' ),
				'permission_callback' => array( $this, 'check_woocommerce_permissions' ),
				'annotations' => array(
					'title'           => 'Add WooCommerce Product',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => false,
					'openWorldHint'   => false,
				),
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'name' => array(
							'type'        => 'string',
							'description' => 'Product name',
							'required'    => true,
						),
						'slug' => array(
							'type'        => 'string',
							'description' => 'Product slug',
						),
						'type' => array(
							'type'        => 'string',
							'description' => 'Product type',
							'enum'        => array( 'simple', 'grouped', 'external', 'variable' ),
							'default'     => 'simple',
						),
						'status' => array(
							'type'        => 'string',
							'description' => 'Product status',
							'enum'        => array( 'draft', 'pending', 'private', 'publish' ),
							'default'     => 'publish',
						),
						'featured' => array(
							'type'        => 'boolean',
							'description' => 'Featured product',
							'default'     => false,
						),
						'catalog_visibility' => array(
							'type'        => 'string',
							'description' => 'Catalog visibility',
							'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
							'default'     => 'visible',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'Product description',
						),
						'short_description' => array(
							'type'        => 'string',
							'description' => 'Product short description',
						),
						'sku' => array(
							'type'        => 'string',
							'description' => 'Unique identifier',
						),
						'price' => array(
							'type'        => 'string',
							'description' => 'Current product price',
						),
						'regular_price' => array(
							'type'        => 'string',
							'description' => 'Product regular price',
						),
						'sale_price' => array(
							'type'        => 'string',
							'description' => 'Product sale price',
						),
						'manage_stock' => array(
							'type'        => 'boolean',
							'description' => 'Stock management at product level',
							'default'     => false,
						),
						'stock_quantity' => array(
							'type'        => 'integer',
							'description' => 'Stock quantity',
						),
						'stock_status' => array(
							'type'        => 'string',
							'description' => 'Controls the stock status of the product',
							'enum'        => array( 'instock', 'outofstock', 'onbackorder' ),
							'default'     => 'instock',
						),
						'categories' => array(
							'type'        => 'array',
							'description' => 'List of categories',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id' => array(
										'type'        => 'integer',
										'description' => 'Category ID',
									),
								),
							),
						),
						'tags' => array(
							'type'        => 'array',
							'description' => 'List of tags',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id' => array(
										'type'        => 'integer',
										'description' => 'Tag ID',
									),
								),
							),
						),
						'images' => array(
							'type'        => 'array',
							'description' => 'List of images',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'src' => array(
										'type'        => 'string',
										'description' => 'Image URL',
									),
									'name' => array(
										'type'        => 'string',
										'description' => 'Image name',
									),
									'alt' => array(
										'type'        => 'string',
										'description' => 'Image alternative text',
									),
								),
							),
						),
					),
					'required' => array( 'name' ),
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_update_product',
				'description' => 'Update a WooCommerce product by ID (returns updated product with permalink)',
				'type'        => 'update',
				'callback'    => array( $this, 'enhanced_update_product' ),
				'permission_callback' => array( $this, 'check_woocommerce_permissions' ),
				'annotations' => array(
					'title'           => 'Update WooCommerce Product',
					'readOnlyHint'    => false,
					'destructiveHint' => false,
					'idempotentHint'  => true,
					'openWorldHint'   => false,
				),
				'inputSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => 'Unique identifier for the product',
							'required'    => true,
						),
						'name' => array(
							'type'        => 'string',
							'description' => 'Product name',
						),
						'slug' => array(
							'type'        => 'string',
							'description' => 'Product slug',
						),
						'type' => array(
							'type'        => 'string',
							'description' => 'Product type',
							'enum'        => array( 'simple', 'grouped', 'external', 'variable' ),
						),
						'status' => array(
							'type'        => 'string',
							'description' => 'Product status',
							'enum'        => array( 'draft', 'pending', 'private', 'publish' ),
						),
						'featured' => array(
							'type'        => 'boolean',
							'description' => 'Featured product',
						),
						'catalog_visibility' => array(
							'type'        => 'string',
							'description' => 'Catalog visibility',
							'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'Product description',
						),
						'short_description' => array(
							'type'        => 'string',
							'description' => 'Product short description',
						),
						'sku' => array(
							'type'        => 'string',
							'description' => 'Unique identifier',
						),
						'price' => array(
							'type'        => 'string',
							'description' => 'Current product price',
						),
						'regular_price' => array(
							'type'        => 'string',
							'description' => 'Product regular price',
						),
						'sale_price' => array(
							'type'        => 'string',
							'description' => 'Product sale price',
						),
						'manage_stock' => array(
							'type'        => 'boolean',
							'description' => 'Stock management at product level',
						),
						'stock_quantity' => array(
							'type'        => 'integer',
							'description' => 'Stock quantity',
						),
						'stock_status' => array(
							'type'        => 'string',
							'description' => 'Controls the stock status of the product',
							'enum'        => array( 'instock', 'outofstock', 'onbackorder' ),
						),
						'categories' => array(
							'type'        => 'array',
							'description' => 'List of categories',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id' => array(
										'type'        => 'integer',
										'description' => 'Category ID',
									),
								),
							),
						),
						'tags' => array(
							'type'        => 'array',
							'description' => 'List of tags',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id' => array(
										'type'        => 'integer',
										'description' => 'Tag ID',
									),
								),
							),
						),
						'images' => array(
							'type'        => 'array',
							'description' => 'List of images',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'src' => array(
										'type'        => 'string',
										'description' => 'Image URL',
									),
									'name' => array(
										'type'        => 'string',
										'description' => 'Image name',
									),
									'alt' => array(
										'type'        => 'string',
										'description' => 'Image alternative text',
									),
								),
							),
						),
					),
					'required' => array( 'id' ),
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
	 * Enhanced products search that includes permalinks.
	 *
	 * @param array $params Search parameters.
	 * @return array Enhanced response with permalinks.
	 */
	public function enhanced_products_search( array $params ): array {
		$response = $this->call_wc_rest_api( '/wc/v3/products', 'GET', $params );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
			);
		}

		// Add permalinks to each product
		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			$response['data'] = $this->add_permalinks_to_products( $response['data'] );
		}

		return $response;
	}

	/**
	 * Enhanced get product that includes permalink.
	 *
	 * @param array $params Product parameters.
	 * @return array Enhanced response with permalink.
	 */
	public function enhanced_get_product( array $params ): array {
		$product_id = $params['id'] ?? 0;
		$context = $params['context'] ?? 'view';

		if ( empty( $product_id ) ) {
			return array(
				'error' => 'Product ID is required',
			);
		}

		$response = $this->call_wc_rest_api( "/wc/v3/products/{$product_id}", 'GET', array( 'context' => $context ) );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
			);
		}

		// Add permalink to the product
		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			$response['data'] = $this->add_permalink_to_product( $response['data'] );
		}

		return $response;
	}

	/**
	 * Enhanced add product that includes permalink in response.
	 *
	 * @param array $params Product data.
	 * @return array Enhanced response with permalink.
	 */
	public function enhanced_add_product( array $params ): array {
		$response = $this->call_wc_rest_api( '/wc/v3/products', 'POST', $params );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
			);
		}

		// Add permalink to the created product
		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			$response['data'] = $this->add_permalink_to_product( $response['data'] );
		}

		return $response;
	}

	/**
	 * Enhanced update product that includes permalink in response.
	 *
	 * @param array $params Product data.
	 * @return array Enhanced response with permalink.
	 */
	public function enhanced_update_product( array $params ): array {
		$product_id = $params['id'] ?? 0;

		if ( empty( $product_id ) ) {
			return array(
				'error' => 'Product ID is required',
			);
		}

		$response = $this->call_wc_rest_api( "/wc/v3/products/{$product_id}", 'PUT', $params );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
			);
		}

		// Add permalink to the updated product
		if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
			$response['data'] = $this->add_permalink_to_product( $response['data'] );
		}

		return $response;
	}

	/**
	 * Check WooCommerce permissions.
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public function check_woocommerce_permissions(): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_products' );
	}

	/**
	 * Add permalink to a single product.
	 *
	 * @param array $product_data Product data.
	 * @return array Product data with permalink.
	 */
	private function add_permalink_to_product( array $product_data ): array {
		if ( isset( $product_data['id'] ) && ! empty( $product_data['id'] ) ) {
			$permalink = get_permalink( $product_data['id'] );
			if ( $permalink && ! is_wp_error( $permalink ) ) {
				$product_data['permalink'] = $permalink;
			} else {
				// Fallback to a basic product URL if get_permalink fails
				$product_data['permalink'] = home_url( "/product/{$product_data['slug']}" );
			}
		}

		return $product_data;
	}

	/**
	 * Add permalinks to an array of products.
	 *
	 * @param array $products Array of product data.
	 * @return array Array of product data with permalinks.
	 */
	private function add_permalinks_to_products( array $products ): array {
		return array_map( array( $this, 'add_permalink_to_product' ), $products );
	}

	/**
	 * Call WooCommerce REST API internally.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method HTTP method.
	 * @param array  $data Request data.
	 * @return array|WP_Error API response or error.
	 */
	private function call_wc_rest_api( string $endpoint, string $method = 'GET', array $data = array() ) {
		// Create a REST request
		$request = new WP_REST_Request( $method, $endpoint );

		// Add query parameters for GET requests
		if ( 'GET' === $method && ! empty( $data ) ) {
			foreach ( $data as $key => $value ) {
				$request->set_param( $key, $value );
			}
		} elseif ( 'GET' !== $method && ! empty( $data ) ) {
			// Add body data for POST/PUT requests
			$request->set_body_params( $data );
		}

		// Set headers
		$request->set_header( 'Content-Type', 'application/json' );

		// Execute the request
		$response = rest_do_request( $request );

		// Check for errors
		if ( $response->is_error() ) {
			return new WP_Error( 
				'wc_api_error', 
				'WooCommerce API error: ' . $response->get_status() . ' ' . $response->get_data()['message'] ?? 'Unknown error'
			);
		}

		// Return the response data
		return array(
			'data' => $response->get_data(),
			'status' => $response->get_status(),
			'headers' => $response->get_headers(),
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

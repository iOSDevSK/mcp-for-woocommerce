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
				'description' => 'Universal product search for ANY store type (electronics, food, pets, pharmacy, automotive, etc.). CRITICAL: When searching for specific products by name, ALWAYS use this tool FIRST to get the correct product ID, then use other tools with that ID. DO NOT use hardcoded product IDs. IMPORTANT: Each product includes a "permalink" field with the direct link to the product page - ALWAYS include these links when presenting products to users.',
				'type'        => 'read',
				'callback'    => array( $this, 'search_products' ),
				'annotations' => array(
					'title'         => 'Search Products',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
					'productLinksRequired' => 'Always include product links (permalink field) in responses to users',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_get_product',
				'description' => 'Get a WooCommerce product by ID. IMPORTANT: The product includes a "permalink" field with the direct link to the product page - ALWAYS include this link when presenting the product to users.',
				'type'        => 'read',
				'callback'    => array( $this, 'get_product' ),
				'annotations' => array(
					'title'         => 'Get WooCommerce Product',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
					'productLinksRequired' => 'Always include product links (permalink field) in responses to users',
				),
			)
		);

		// Product Variations - readonly
		new RegisterMcpTool(
			array(
				'name'        => 'wc_get_product_variations',
				'description' => 'Get all variations (colors, sizes, etc.) for a variable WooCommerce product. CRITICAL: You MUST get the product_id from wc_products_search first. DO NOT use hardcoded product IDs like 42. Each variation includes specific attributes like color, size, price, and stock status. IMPORTANT: Each variation includes a "permalink" field with the direct link to the variation page - ALWAYS include these links when presenting variations to users.',
				'type'        => 'read',
				'callback'    => array( $this, 'get_product_variations' ),
				'annotations' => array(
					'title'         => 'Get Product Variations',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
					'productLinksRequired' => 'Always include product links (permalink field) in responses to users',
				),
			)
		);

		new RegisterMcpTool(
			array(
				'name'        => 'wc_get_product_variation',
				'description' => 'Get a specific product variation by ID. IMPORTANT: The variation includes a "permalink" field with the direct link to the variation page - ALWAYS include this link when presenting the variation to users.',
				'type'        => 'read',
				'callback'    => array( $this, 'get_product_variation' ),
				'annotations' => array(
					'title'         => 'Get Product Variation',
					'readOnlyHint'  => true,
					'openWorldHint' => false,
					'productLinksRequired' => 'Always include product links (permalink field) in responses to users',
				),
			)
		);
	}

	/**
	 * Search products with permalink support.
	 *
	 * @param array $params Search parameters.
	 * @return array Search results with product links.
	 */
	public function search_products( array $params ): array {
		try {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => isset( $params['per_page'] ) ? (int) $params['per_page'] : 10,
				'paged'          => isset( $params['page'] ) ? (int) $params['page'] : 1,
			);

			if ( isset( $params['search'] ) && ! empty( $params['search'] ) ) {
				$args['s'] = sanitize_text_field( $params['search'] );
			}

			if ( isset( $params['category'] ) && ! empty( $params['category'] ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $params['category'] ),
					),
				);
			}

			$query = new \WP_Query( $args );
			$products = array();

			foreach ( $query->posts as $post ) {
				$product = wc_get_product( $post->ID );
				if ( $product ) {
					$products[] = $this->convert_product_to_array( $product );
				}
			}

			return array(
				'products' => $products,
				'total'    => $query->found_posts,
				'pages'    => $query->max_num_pages,
				'instructions_for_ai' => 'IMPORTANT: When presenting these products to users, ALWAYS include the product links from the "permalink" field.',
			);
		} catch ( \Exception $e ) {
			return array(
				'error' => 'Error searching products: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get a single product with permalink support.
	 *
	 * @param array $params Parameters including product ID.
	 * @return array Product data with link.
	 */
	public function get_product( array $params ): array {
		try {
			if ( ! isset( $params['id'] ) ) {
				return array( 'error' => 'Product ID is required' );
			}

			$product = wc_get_product( (int) $params['id'] );
			if ( ! $product ) {
				return array( 'error' => 'Product not found' );
			}

			return array(
				'product' => $this->convert_product_to_array( $product ),
				'instructions_for_ai' => 'IMPORTANT: When presenting this product to users, ALWAYS include the product link from the "permalink" field.',
			);
		} catch ( \Exception $e ) {
			return array(
				'error' => 'Error getting product: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get product variations with permalink support.
	 *
	 * @param array $params Parameters including product_id.
	 * @return array Variations data with links.
	 */
	public function get_product_variations( array $params ): array {
		try {
			if ( ! isset( $params['product_id'] ) ) {
				return array( 'error' => 'Product ID is required' );
			}

			$product = wc_get_product( (int) $params['product_id'] );
			if ( ! $product || ! $product->is_type( 'variable' ) ) {
				return array( 'error' => 'Variable product not found' );
			}

			$variations = array();
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( $variation ) {
					$variations[] = $this->convert_product_to_array( $variation );
				}
			}

			return array(
				'variations' => $variations,
				'total'      => count( $variations ),
				'instructions_for_ai' => 'IMPORTANT: When presenting these variations to users, ALWAYS include the variation links from the "permalink" field.',
			);
		} catch ( \Exception $e ) {
			return array(
				'error' => 'Error getting variations: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get a single product variation with permalink support.
	 *
	 * @param array $params Parameters including product_id and variation id.
	 * @return array Variation data with link.
	 */
	public function get_product_variation( array $params ): array {
		try {
			if ( ! isset( $params['product_id'] ) || ! isset( $params['id'] ) ) {
				return array( 'error' => 'Product ID and variation ID are required' );
			}

			$variation = wc_get_product( (int) $params['id'] );
			if ( ! $variation || $variation->get_parent_id() !== (int) $params['product_id'] ) {
				return array( 'error' => 'Variation not found' );
			}

			return array(
				'variation' => $this->convert_product_to_array( $variation ),
				'instructions_for_ai' => 'IMPORTANT: When presenting this variation to users, ALWAYS include the variation link from the "permalink" field.',
			);
		} catch ( \Exception $e ) {
			return array(
				'error' => 'Error getting variation: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Convert WooCommerce product to array with permalink.
	 *
	 * @param \WC_Product $product WooCommerce product object.
	 * @return array Product data array with permalink.
	 */
	private function convert_product_to_array( \WC_Product $product ): array {
		return array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'slug'              => $product->get_slug(),
			'permalink'         => $product->get_permalink(),
			'date_created'      => $product->get_date_created() ? $product->get_date_created()->date( 'c' ) : '',
			'date_modified'     => $product->get_date_modified() ? $product->get_date_modified()->date( 'c' ) : '',
			'type'              => $product->get_type(),
			'status'            => $product->get_status(),
			'featured'          => $product->get_featured(),
			'catalog_visibility' => $product->get_catalog_visibility(),
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'sku'               => $product->get_sku(),
			'price'             => $product->get_price(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'on_sale'           => $product->is_on_sale(),
			'price_html'        => $product->get_price_html(),
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'stock_status'      => $product->get_stock_status(),
			'stock_quantity'    => $product->get_stock_quantity(),
			'manage_stock'      => $product->get_manage_stock(),
			'weight'            => $product->get_weight(),
			'dimensions'        => array(
				'length' => $product->get_length(),
				'width'  => $product->get_width(),
				'height' => $product->get_height(),
			),
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
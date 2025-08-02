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
			// Use WooCommerce API but add permalink data
			$request = new \WP_REST_Request( 'GET', '/wc/v3/products' );
			foreach ( $params as $key => $value ) {
				$request->set_param( $key, $value );
			}
			
			$api = new \WC_REST_Products_Controller();
			$response = $api->get_items( $request );
			
			if ( is_wp_error( $response ) ) {
				return array( 'error' => $response->get_error_message() );
			}
			
			$products = $response->get_data();
			
			// Add permalink to each product
			foreach ( $products as &$product ) {
				if ( isset( $product['id'] ) ) {
					$wc_product = wc_get_product( $product['id'] );
					if ( $wc_product ) {
						$product['permalink'] = $wc_product->get_permalink();
					}
				}
			}
			
			return array(
				'products' => $products,
				'instructions_for_ai' => 'CRITICAL: When presenting these products to users, you MUST include the product links from the "permalink" field for each product. Users need clickable links to access products. This is mandatory - do not skip the links.',
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

			$request = new \WP_REST_Request( 'GET', '/wc/v3/products/' . $params['id'] );
			$api = new \WC_REST_Products_Controller();
			$response = $api->get_item( $request );
			
			if ( is_wp_error( $response ) ) {
				return array( 'error' => $response->get_error_message() );
			}
			
			$product_data = $response->get_data();
			
			// Add permalink
			$wc_product = wc_get_product( $params['id'] );
			if ( $wc_product ) {
				$product_data['permalink'] = $wc_product->get_permalink();
			}
			
			return array(
				'product' => $product_data,
				'instructions_for_ai' => 'CRITICAL: When presenting this product to users, you MUST include the product link from the "permalink" field. Users need clickable links to access products.',
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

			$request = new \WP_REST_Request( 'GET', '/wc/v3/products/' . $params['product_id'] . '/variations' );
			foreach ( $params as $key => $value ) {
				if ( $key !== 'product_id' ) {
					$request->set_param( $key, $value );
				}
			}
			
			$api = new \WC_REST_Product_Variations_Controller();
			$response = $api->get_items( $request );
			
			if ( is_wp_error( $response ) ) {
				return array( 'error' => $response->get_error_message() );
			}
			
			$variations = $response->get_data();
			
			// Add permalink to each variation
			foreach ( $variations as &$variation ) {
				if ( isset( $variation['id'] ) ) {
					$wc_variation = wc_get_product( $variation['id'] );
					if ( $wc_variation ) {
						$variation['permalink'] = $wc_variation->get_permalink();
					}
				}
			}
			
			return array(
				'variations' => $variations,
				'instructions_for_ai' => 'CRITICAL: When presenting these variations to users, you MUST include the variation links from the "permalink" field for each variation. Users need clickable links to access products.',
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

			$request = new \WP_REST_Request( 'GET', '/wc/v3/products/' . $params['product_id'] . '/variations/' . $params['id'] );
			$api = new \WC_REST_Product_Variations_Controller();
			$response = $api->get_item( $request );
			
			if ( is_wp_error( $response ) ) {
				return array( 'error' => $response->get_error_message() );
			}
			
			$variation_data = $response->get_data();
			
			// Add permalink
			$wc_variation = wc_get_product( $params['id'] );
			if ( $wc_variation ) {
				$variation_data['permalink'] = $wc_variation->get_permalink();
			}
			
			return array(
				'variation' => $variation_data,
				'instructions_for_ai' => 'CRITICAL: When presenting this variation to users, you MUST include the variation link from the "permalink" field. Users need clickable links to access products.',
			);
		} catch ( \Exception $e ) {
			return array(
				'error' => 'Error getting variation: ' . $e->getMessage(),
			);
		}
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
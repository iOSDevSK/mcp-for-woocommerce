<?php
/**
 * Test class for McpWooIntelligentSearch
 *
 * @package Automattic\WordpressMcp\Tests\Tools
 */

namespace Automattic\WordpressMcp\Tests\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Tools\McpWooIntelligentSearch;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;
use WC_Product_Simple;
use WC_Product_Variable;

/**
 * Test class for McpWooIntelligentSearch
 */
final class McpWooIntelligentSearchTest extends WP_UnitTestCase {

	/**
	 * The MCP instance.
	 *
	 * @var WpMcp
	 */
	private WpMcp $mcp;

	/**
	 * The admin user.
	 *
	 * @var WP_User
	 */
	private WP_User $admin_user;

	/**
	 * The intelligent search instance.
	 *
	 * @var McpWooIntelligentSearch
	 */
	private McpWooIntelligentSearch $intelligent_search;

	/**
	 * Test products created for testing
	 *
	 * @var array
	 */
	private array $test_products = array();

	/**
	 * Test categories created for testing
	 *
	 * @var array
	 */
	private array $test_categories = array();

	/**
	 * Test tags created for testing
	 *
	 * @var array
	 */
	private array $test_tags = array();

	/**
	 * Set up the test.
	 */
	public function set_up(): void {
		parent::set_up();

		// Create an admin user.
		$this->admin_user = $this->factory->user->create_and_get(
			array(
				'role' => 'administrator',
			)
		);

		// Get the MCP instance.
		$this->mcp = WPMCP();

		// Activate WooCommerce if not already active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			activate_plugin( 'woocommerce' );
		}

		// Initialize WooCommerce.
		if ( ! did_action( 'woocommerce_init' ) ) {
			do_action( 'woocommerce_init' );
		}

		// Initialize the REST API and MCP.
		do_action( 'rest_api_init' );

		// Create the intelligent search instance.
		$this->intelligent_search = new McpWooIntelligentSearch();

		// Create test data.
		$this->create_test_data();
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down(): void {
		parent::tear_down();

		// Clean up test data.
		$this->cleanup_test_data();

		// Deactivate WooCommerce after tests.
		deactivate_plugins( 'woocommerce' );
	}

	/**
	 * Create test data for testing.
	 */
	private function create_test_data(): void {
		// Skip if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Create test categories.
		$electronics_cat = wp_insert_term( 'Electronics', 'product_cat' );
		$phones_cat = wp_insert_term( 'Phones', 'product_cat', array( 'parent' => $electronics_cat['term_id'] ) );
		$clothing_cat = wp_insert_term( 'Clothing', 'product_cat' );
		$books_cat = wp_insert_term( 'Books', 'product_cat' );

		if ( ! is_wp_error( $electronics_cat ) ) {
			$this->test_categories[] = $electronics_cat['term_id'];
		}
		if ( ! is_wp_error( $phones_cat ) ) {
			$this->test_categories[] = $phones_cat['term_id'];
		}
		if ( ! is_wp_error( $clothing_cat ) ) {
			$this->test_categories[] = $clothing_cat['term_id'];
		}
		if ( ! is_wp_error( $books_cat ) ) {
			$this->test_categories[] = $books_cat['term_id'];
		}

		// Create test tags.
		$sale_tag = wp_insert_term( 'Sale', 'product_tag' );
		$premium_tag = wp_insert_term( 'Premium', 'product_tag' );

		if ( ! is_wp_error( $sale_tag ) ) {
			$this->test_tags[] = $sale_tag['term_id'];
		}
		if ( ! is_wp_error( $premium_tag ) ) {
			$this->test_tags[] = $premium_tag['term_id'];
		}

		// Create test products.
		$phone_product = new WC_Product_Simple();
		$phone_product->set_name( 'iPhone 15' );
		$phone_product->set_regular_price( 999 );
		$phone_product->set_sale_price( 899 );
		$phone_product->set_description( 'Latest iPhone with advanced features' );
		$phone_product->set_category_ids( array( $phones_cat['term_id'] ) );
		$phone_product->set_tag_ids( array( $sale_tag['term_id'] ) );
		$phone_product->save();
		$this->test_products[] = $phone_product->get_id();

		$laptop_product = new WC_Product_Simple();
		$laptop_product->set_name( 'MacBook Pro' );
		$laptop_product->set_regular_price( 2499 );
		$laptop_product->set_description( 'High-performance laptop for professionals' );
		$laptop_product->set_category_ids( array( $electronics_cat['term_id'] ) );
		$laptop_product->set_tag_ids( array( $premium_tag['term_id'] ) );
		$laptop_product->save();
		$this->test_products[] = $laptop_product->get_id();

		$shirt_product = new WC_Product_Simple();
		$shirt_product->set_name( 'Cotton T-Shirt' );
		$shirt_product->set_regular_price( 29 );
		$shirt_product->set_description( 'Comfortable cotton t-shirt' );
		$shirt_product->set_category_ids( array( $clothing_cat['term_id'] ) );
		$shirt_product->save();
		$this->test_products[] = $shirt_product->get_id();

		$book_product = new WC_Product_Simple();
		$book_product->set_name( 'Programming Book' );
		$book_product->set_regular_price( 49 );
		$book_product->set_description( 'Learn programming with this comprehensive guide' );
		$book_product->set_category_ids( array( $books_cat['term_id'] ) );
		$book_product->save();
		$this->test_products[] = $book_product->get_id();

		$cheap_product = new WC_Product_Simple();
		$cheap_product->set_name( 'Budget Item' );
		$cheap_product->set_regular_price( 5 );
		$cheap_product->set_description( 'Affordable budget item' );
		$cheap_product->save();
		$this->test_products[] = $cheap_product->get_id();
	}

	/**
	 * Clean up test data.
	 */
	private function cleanup_test_data(): void {
		// Delete test products.
		foreach ( $this->test_products as $product_id ) {
			wp_delete_post( $product_id, true );
		}

		// Delete test categories.
		foreach ( $this->test_categories as $category_id ) {
			wp_delete_term( $category_id, 'product_cat' );
		}

		// Delete test tags.
		foreach ( $this->test_tags as $tag_id ) {
			wp_delete_term( $tag_id, 'product_tag' );
		}
	}

	/**
	 * Test the wc_intelligent_search tool registration.
	 */
	public function test_tool_registration(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Trigger the registration.
		$this->intelligent_search->register_tools();

		// Check if the tools are registered by making a request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/list',
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );
		wp_set_current_user( $this->admin_user->ID );

		$response = rest_do_request( $request );
		$response_data = json_decode( $response->get_data()['content'][0]['text'], true );

		// Check that our tools are registered.
		$tool_names = array_column( $response_data, 'name' );
		$this->assertContains( 'wc_intelligent_search', $tool_names );
		$this->assertContains( 'wc_analyze_search_intent_helper', $tool_names );
	}

	/**
	 * Test intelligent search with simple query.
	 */
	public function test_intelligent_search_simple(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'iPhone',
			'per_page' => 10,
			'page' => 1,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertTrue( $result['success'] );
		$this->assertGreaterThan( 0, count( $result['products'] ) );
		$this->assertContains( 'iPhone', $result['products'][0]['name'] );
	}

	/**
	 * Test intelligent search with price intent.
	 */
	public function test_intelligent_search_price_intent(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'cheapest products',
			'per_page' => 10,
			'page' => 1,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertTrue( $result['success'] );
		$this->assertGreaterThan( 0, count( $result['products'] ) );
		// Should return products sorted by price ascending.
		$this->assertEquals( 'Budget Item', $result['products'][0]['name'] );
	}

	/**
	 * Test intelligent search with promotional intent.
	 */
	public function test_intelligent_search_promotional_intent(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'products on sale',
			'per_page' => 10,
			'page' => 1,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertTrue( $result['success'] );
		$this->assertGreaterThan( 0, count( $result['products'] ) );
		// Should return products that are on sale.
		$found_sale_product = false;
		foreach ( $result['products'] as $product ) {
			if ( $product['on_sale'] ) {
				$found_sale_product = true;
				break;
			}
		}
		$this->assertTrue( $found_sale_product );
	}

	/**
	 * Test intelligent search with category matching.
	 */
	public function test_intelligent_search_category_matching(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'electronics',
			'per_page' => 10,
			'page' => 1,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertTrue( $result['success'] );
		$this->assertGreaterThan( 0, count( $result['products'] ) );
		// Should return products from electronics category.
		$found_electronics = false;
		foreach ( $result['products'] as $product ) {
			foreach ( $product['categories'] as $category ) {
				if ( strtolower( $category['name'] ) === 'electronics' || strtolower( $category['name'] ) === 'phones' ) {
					$found_electronics = true;
					break 2;
				}
			}
		}
		$this->assertTrue( $found_electronics );
	}

	/**
	 * Test intelligent search with no results fallback.
	 */
	public function test_intelligent_search_no_results_fallback(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'nonexistent product xyz123',
			'per_page' => 10,
			'page' => 1,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'alternatives', $result );
		$this->assertArrayHasKey( 'available_categories', $result['alternatives'] );
		$this->assertArrayHasKey( 'suggestions', $result['alternatives'] );
	}

	/**
	 * Test intelligent search with debug mode.
	 */
	public function test_intelligent_search_debug_mode(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'iPhone',
			'per_page' => 10,
			'page' => 1,
			'debug' => true,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'debug', $result );
		$this->assertArrayHasKey( 'intent_analysis', $result['debug'] );
	}

	/**
	 * Test analyze search intent function.
	 */
	public function test_analyze_search_intent(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'user_query' => 'cheapest phones on sale',
			'available_categories' => array(
				array( 'id' => 1, 'name' => 'Electronics', 'slug' => 'electronics' ),
				array( 'id' => 2, 'name' => 'Phones', 'slug' => 'phones' ),
			),
			'available_tags' => array(
				array( 'id' => 1, 'name' => 'Sale', 'slug' => 'sale' ),
			),
		);

		$result = $this->intelligent_search->analyze_search_intent( $params );

		$this->assertEquals( 'cheapest phones on sale', $result['original_query'] );
		$this->assertContains( 'price_asc', $result['detected_intents'] );
		$this->assertContains( 'on_sale', $result['detected_intents'] );
		$this->assertGreaterThan( 0, count( $result['matched_categories'] ) );
		$this->assertEquals( 'phones', strtolower( $result['matched_categories'][0]['name'] ) );
	}

	/**
	 * Test analyze search intent with temporal intent.
	 */
	public function test_analyze_search_intent_temporal(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'user_query' => 'latest electronics',
			'available_categories' => array(
				array( 'id' => 1, 'name' => 'Electronics', 'slug' => 'electronics' ),
			),
			'available_tags' => array(),
		);

		$result = $this->intelligent_search->analyze_search_intent( $params );

		$this->assertContains( 'date_desc', $result['detected_intents'] );
		$this->assertEquals( 'date', $result['search_params']['orderby'] );
		$this->assertEquals( 'desc', $result['search_params']['order'] );
	}

	/**
	 * Test analyze search intent with premium intent.
	 */
	public function test_analyze_search_intent_premium(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'user_query' => 'most expensive luxury items',
			'available_categories' => array(),
			'available_tags' => array(),
		);

		$result = $this->intelligent_search->analyze_search_intent( $params );

		$this->assertContains( 'price_desc', $result['detected_intents'] );
		$this->assertEquals( 'price', $result['search_params']['orderby'] );
		$this->assertEquals( 'desc', $result['search_params']['order'] );
	}

	/**
	 * Test intelligent search with empty query.
	 */
	public function test_intelligent_search_empty_query(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => '',
			'per_page' => 10,
			'page' => 1,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertArrayHasKey( 'error', $result );
		$this->assertEquals( 'Search query is required', $result['error'] );
		$this->assertArrayHasKey( 'suggestion', $result );
	}

	/**
	 * Test intelligent search tool via REST API.
	 */
	public function test_intelligent_search_rest_api(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name' => 'wc_intelligent_search',
					'arguments' => array(
						'query' => 'iPhone',
						'per_page' => 5,
						'page' => 1,
					),
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );
		wp_set_current_user( $this->admin_user->ID );

		$response = rest_do_request( $request );
		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response_json['success'] );
		$this->assertGreaterThan( 0, count( $response_json['products'] ) );
	}

	/**
	 * Test analyze search intent helper tool via REST API.
	 */
	public function test_analyze_search_intent_helper_rest_api(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name' => 'wc_analyze_search_intent_helper',
					'arguments' => array(
						'user_query' => 'cheapest phones on sale',
					),
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );
		wp_set_current_user( $this->admin_user->ID );

		$response = rest_do_request( $request );
		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'cheapest phones on sale', $response_json['original_query'] );
		$this->assertIsArray( $response_json['detected_intents'] );
		$this->assertIsArray( $response_json['matched_categories'] );
	}

	/**
	 * Test search with pagination.
	 */
	public function test_intelligent_search_pagination(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'product',
			'per_page' => 2,
			'page' => 1,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertTrue( $result['success'] );
		$this->assertLessThanOrEqual( 2, count( $result['products'] ) );
		$this->assertGreaterThan( 0, $result['total_products'] );
	}

	/**
	 * Test fuzzy category matching.
	 */
	public function test_fuzzy_category_matching(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'elektronics', // Intentional typo
			'per_page' => 10,
			'page' => 1,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		// Should still find electronics products despite typo
		$this->assertTrue( $result['success'] );
		$this->assertGreaterThan( 0, count( $result['products'] ) );
	}

	/**
	 * Test search with multiple intents.
	 */
	public function test_search_multiple_intents(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		$params = array(
			'query' => 'latest cheapest electronics on sale',
			'per_page' => 10,
			'page' => 1,
			'debug' => true,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'debug', $result );
		$intent_analysis = $result['debug']['intent_analysis'];
		
		// Should detect multiple intents
		$this->assertGreaterThan( 1, count( $intent_analysis['detected_intents'] ) );
	}

	/**
	 * Test broader category search fallback.
	 */
	public function test_broader_category_fallback(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Search for a specific subcategory with restrictive filters
		$params = array(
			'query' => 'expensive phones on sale', // Very restrictive
			'per_page' => 10,
			'page' => 1,
			'debug' => true,
		);

		$result = $this->intelligent_search->intelligent_search( $params );

		// Should find products, possibly falling back to broader categories
		$this->assertTrue( $result['success'] );
		$this->assertGreaterThan( 0, count( $result['products'] ) );
	}
}
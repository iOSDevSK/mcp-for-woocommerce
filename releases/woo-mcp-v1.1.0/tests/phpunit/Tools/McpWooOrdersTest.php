<?php
/**
 * Test class for McpWooOrders
 *
 * @package Automattic\WordpressMcp\Tests\Tools
 */

namespace Automattic\WordpressMcp\Tests\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Tools\McpWooOrders;
use WC_Coupon;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;
use WC_Product_Simple;
use WC_Product_Grouped;
use WC_Product_External;
use WC_Product_Variable;

/**
 * Test class for McpWooOrders
 */
final class McpWooOrdersTest extends WP_UnitTestCase {

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
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down(): void {
		parent::tear_down();

		// Deactivate WooCommerce after tests.
		deactivate_plugins( 'woocommerce' );
	}

	/**
	 * Test the wc_orders_search tool.
	 */
	public function test_wc_orders_search_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// face some random orders.
		$order = wc_create_order();
		$order->set_status( 'processing' );
		$order->save();

		$order = wc_create_order();
		$order->set_status( 'cancelled' );
		$order->save();

		$order = wc_create_order();
		$order->set_status( 'refunded' );
		$order->save();

		$order = wc_create_order();
		$order->set_status( 'pending' );
		$order->save();

		$order = wc_create_order();
		$order->set_status( 'completed' );
		$order->save();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wc_orders_search',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 5, $response_json );
	}

	/**
	 * Test the wc_reports_coupons_totals tool.
	 */
	public function test_wc_reports_coupons_totals_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a coupon.
		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-coupon-50' );
		$coupon->set_amount( 50 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wc_reports_coupons_totals',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );

		// Find the fixed_cart coupon in the response
		$fixed_cart_coupon = null;
		foreach ( $response_json as $coupon ) {
			if ( $coupon['slug'] === 'fixed_cart' ) {
				$fixed_cart_coupon = $coupon;
				break;
			}
		}

		// Assert that we found the fixed_cart coupon and it has the expected total
		$this->assertNotNull( $fixed_cart_coupon, 'Fixed cart coupon not found in response' );
		$this->assertEquals( 'Fixed cart discount', $fixed_cart_coupon['name'] );
		$this->assertEquals( 1, $fixed_cart_coupon['total'] );
	}

	/**
	 * Test the wc_reports_customers_totals tool.
	 */
	public function test_wc_reports_customers_totals_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a customers.
		$customer = new \WC_Customer();
		$customer->set_first_name( 'John' );
		$customer->set_last_name( 'Doe' );
		$customer->set_email( 'john.doe@example.com' );
		$customer->save();

		// Create a order.
		$order = wc_create_order();
		$order->set_customer_id( $customer->get_id() );
		$order->set_status( 'completed' );
		$order->save();

		$customer = new \WC_Customer();
		$customer->set_first_name( 'Jane' );
		$customer->set_last_name( 'Doe' );
		$customer->set_email( 'jane.doe@example.com' );
		$customer->save();

		$customer = new \WC_Customer();
		$customer->set_first_name( 'Jim' );
		$customer->set_last_name( 'Beam' );
		$customer->set_email( 'jim.beam@example.com' );
		$customer->save();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wc_reports_customers_totals',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );

		// Verify customer totals
		$this->assertCount( 2, $response_json, 'There should be 2 customer types' );

		// Find paying and non-paying customer entries
		$paying_customers     = null;
		$non_paying_customers = null;
		foreach ( $response_json as $entry ) {
			if ( 'paying' === $entry['slug'] ) {
				$paying_customers = $entry;
			} elseif ( 'non_paying' === $entry['slug'] ) {
				$non_paying_customers = $entry;
			}
		}

		// Verify paying customers
		$this->assertNotNull( $paying_customers, 'Paying customers entry not found' );
		$this->assertEquals( 'Paying customer', $paying_customers['name'] );
		$this->assertEquals( 1, $paying_customers['total'], 'There should be 1 paying customer' );

		// Verify non-paying customers
		$this->assertNotNull( $non_paying_customers, 'Non-paying customers entry not found' );
		$this->assertEquals( 'Non-paying customer', $non_paying_customers['name'] );
		$this->assertEquals( 2, $non_paying_customers['total'], 'There should be 2 non-paying customers' );
	}

	/**
	 * Test the wc_reports_orders_totals tool.
	 */
	public function test_wc_reports_orders_totals_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create some random orders.
		$order = wc_create_order();
		$order->set_status( 'processing' );
		$order->save();

		$order = wc_create_order();
		$order->set_status( 'cancelled' );
		$order->save();

		$order = wc_create_order();
		$order->set_status( 'refunded' );
		$order->save();

		$order = wc_create_order();
		$order->set_status( 'pending' );
		$order->save();

		$order = wc_create_order();
		$order->set_status( 'completed' );
		$order->save();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wc_reports_orders_totals',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );

		// Verify the response structure and content.
		$this->assertIsArray( $response_json );
		$this->assertCount( 8, $response_json, 'There should be 8 order status types' );

		// Define expected order statuses.
		$expected_statuses = array(
			'pending'        => array(
				'name'  => 'Pending payment',
				'total' => 1,
			),
			'processing'     => array(
				'name'  => 'Processing',
				'total' => 1,
			),
			'on-hold'        => array(
				'name'  => 'On hold',
				'total' => 0,
			),
			'completed'      => array(
				'name'  => 'Completed',
				'total' => 1,
			),
			'cancelled'      => array(
				'name'  => 'Cancelled',
				'total' => 1,
			),
			'refunded'       => array(
				'name'  => 'Refunded',
				'total' => 1,
			),
			'failed'         => array(
				'name'  => 'Failed',
				'total' => 0,
			),
			'checkout-draft' => array(
				'name'  => 'Draft',
				'total' => 0,
			),
		);

		// Verify each order status entry.
		foreach ( $expected_statuses as $slug => $expected ) {
			$status_entry = null;
			foreach ( $response_json as $entry ) {
				if ( $slug === $entry['slug'] ) {
					$status_entry = $entry;
					break;
				}
			}

			$this->assertNotNull( $status_entry, "Status entry for '{$slug}' not found" );
			$this->assertEquals( $expected['name'], $status_entry['name'], "Incorrect name for status '{$slug}'" );
			$this->assertEquals( $expected['total'], $status_entry['total'], "Incorrect total for status '{$slug}'" );
		}
	}

	/**
	 * Test the wc_reports_products_totals tool.
	 */
	public function test_wc_reports_products_totals_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a simple product.
		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Test Simple Product' );
		$simple_product->set_regular_price( 100 );
		$simple_product->save();

		// Create a grouped product.
		$grouped_product = new WC_Product_Grouped();
		$grouped_product->set_name( 'Test Grouped Product' );
		$grouped_product->save();

		// Create an external/affiliate product.
		$external_product = new WC_Product_External();
		$external_product->set_name( 'Test External Product' );
		$external_product->set_product_url( 'https://example.com' );
		$external_product->set_button_text( 'Buy Now' );
		$external_product->save();

		// Create a variable product.
		$variable_product = new WC_Product_Variable();
		$variable_product->set_name( 'Test Variable Product' );
		$variable_product->save();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wc_reports_products_totals',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );

		// Verify the response structure and content.
		$this->assertIsArray( $response_json );
		$this->assertCount( 4, $response_json, 'There should be 4 product types' );

		// Define expected product types and their counts.
		$expected_products = array(
			'simple'   => array(
				'name'  => 'Simple product',
				'total' => 1,
			),
			'grouped'  => array(
				'name'  => 'Grouped product',
				'total' => 1,
			),
			'external' => array(
				'name'  => 'External/Affiliate product',
				'total' => 1,
			),
			'variable' => array(
				'name'  => 'Variable product',
				'total' => 1,
			),
		);

		// Verify each product type entry.
		foreach ( $expected_products as $slug => $expected ) {
			$product_entry = null;
			foreach ( $response_json as $entry ) {
				if ( $slug === $entry['slug'] ) {
					$product_entry = $entry;
					break;
				}
			}

			$this->assertNotNull( $product_entry, "Product entry for '{$slug}' not found" );
			$this->assertEquals( $expected['name'], $product_entry['name'], "Incorrect name for product type '{$slug}'" );
			$this->assertEquals( $expected['total'], $product_entry['total'], "Incorrect total for product type '{$slug}'" );
		}
	}

	/**
	 * Test the wc_reports_reviews_totals tool.
	 */
	public function test_wc_reports_reviews_totals_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create products with reviews.
		$product1 = new WC_Product_Simple();
		$product1->set_name( 'Test Product 1' );
		$product1->set_regular_price( 100 );
		$product1->save();

		$product2 = new WC_Product_Simple();
		$product2->set_name( 'Test Product 2' );
		$product2->set_regular_price( 200 );
		$product2->save();

		// Create reviews for product 1.
		$review1 = array(
			'comment_post_ID'      => $product1->get_id(),
			'comment_author'       => 'John Doe',
			'comment_author_email' => 'john@example.com',
			'comment_content'      => 'Great product!',
			'comment_approved'     => 1,
			'comment_type'         => 'review',
			'comment_meta'         => array(
				'rating' => 5,
			),
		);
		wp_insert_comment( $review1 );

		$review2 = array(
			'comment_post_ID'      => $product1->get_id(),
			'comment_author'       => 'Jane Doe',
			'comment_author_email' => 'jane@example.com',
			'comment_content'      => 'Average product.',
			'comment_approved'     => 1,
			'comment_type'         => 'review',
			'comment_meta'         => array(
				'rating' => 3,
			),
		);
		wp_insert_comment( $review2 );

		// Create reviews for product 2.
		$review3 = array(
			'comment_post_ID'      => $product2->get_id(),
			'comment_author'       => 'Jim Beam',
			'comment_author_email' => 'jim@example.com',
			'comment_content'      => 'Terrible product!',
			'comment_approved'     => 1,
			'comment_type'         => 'review',
			'comment_meta'         => array(
				'rating' => 1,
			),
		);
		wp_insert_comment( $review3 );

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wc_reports_reviews_totals',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );

		// Verify the response structure and content.
		$this->assertIsArray( $response_json );
		$this->assertCount( 5, $response_json, 'There should be 5 rating categories' );

		// Define expected review ratings and their counts.
		$expected_ratings = array(
			'rated_1_out_of_5' => array(
				'name'  => 'Rated 1 out of 5',
				'total' => 1,
			),
			'rated_2_out_of_5' => array(
				'name'  => 'Rated 2 out of 5',
				'total' => 0,
			),
			'rated_3_out_of_5' => array(
				'name'  => 'Rated 3 out of 5',
				'total' => 1,
			),
			'rated_4_out_of_5' => array(
				'name'  => 'Rated 4 out of 5',
				'total' => 0,
			),
			'rated_5_out_of_5' => array(
				'name'  => 'Rated 5 out of 5',
				'total' => 1,
			),
		);

		// Verify each rating entry.
		foreach ( $expected_ratings as $slug => $expected ) {
			$rating_entry = null;
			foreach ( $response_json as $entry ) {
				if ( $slug === $entry['slug'] ) {
					$rating_entry = $entry;
					break;
				}
			}

			$this->assertNotNull( $rating_entry, "Rating entry for '{$slug}' not found" );
			$this->assertEquals( $expected['name'], $rating_entry['name'], "Incorrect name for rating '{$slug}'" );
			$this->assertEquals( $expected['total'], $rating_entry['total'], "Incorrect total for rating '{$slug}'" );
		}
	}

	/**
	 * Test the wc_reports_sales tool.
	 */
	public function test_wc_reports_sales_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create products.
		$product1 = new WC_Product_Simple();
		$product1->set_name( 'Test Product 1' );
		$product1->set_regular_price( 100 );
		$product1->save();

		$product2 = new WC_Product_Simple();
		$product2->set_name( 'Test Product 2' );
		$product2->set_regular_price( 200 );
		$product2->save();

		// Create orders with products.
		$order1 = wc_create_order();
		$order1->add_product( $product1, 2 ); // 2 x $100 = $200
		$order1->add_product( $product2, 1 ); // 1 x $200 = $200
		$order1->set_status( 'completed' );
		$order1->calculate_totals();
		$order1->save();

		$order2 = wc_create_order();
		$order2->add_product( $product1, 1 ); // 1 x $100 = $100
		$order2->set_status( 'completed' );
		$order2->calculate_totals();
		$order2->save();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wc_reports_sales',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response      = rest_do_request( $request );
		$response_json = json_decode( $response->get_data()['content'][0]['text'], true );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );

		// Verify sales data.
		$sales_data = $response_json[0];
		$this->assertEquals( '500.00', $sales_data['total_sales'] ); // $200 + $200 + $100 = $500
		$this->assertEquals( '500.00', $sales_data['net_sales'] );
		$this->assertEquals( 2, $sales_data['total_orders'] );
		$this->assertEquals( 4, $sales_data['total_items'] ); // 2 + 1 + 1 = 4 items
		$this->assertEquals( '0.00', $sales_data['total_tax'] );
		$this->assertEquals( '0.00', $sales_data['total_shipping'] );
		$this->assertEquals( 0, $sales_data['total_refunds'] );
		$this->assertEquals( '0.00', $sales_data['total_discount'] );
		$this->assertEquals( 'day', $sales_data['totals_grouped_by'] );

		// Verify totals for today's date.
		$today = gmdate( 'Y-m-d' );
		$this->assertArrayHasKey( $today, $sales_data['totals'] );
		$today_totals = $sales_data['totals'][ $today ];
		$this->assertEquals( '500.00', $today_totals['sales'] );
		$this->assertEquals( 2, $today_totals['orders'] );
		$this->assertEquals( 4, $today_totals['items'] );
		$this->assertEquals( '0.00', $today_totals['tax'] );
		$this->assertEquals( '0.00', $today_totals['shipping'] );
		$this->assertEquals( '0.00', $today_totals['discount'] );
		$this->assertEquals( 0, $today_totals['customers'] );
	}
}

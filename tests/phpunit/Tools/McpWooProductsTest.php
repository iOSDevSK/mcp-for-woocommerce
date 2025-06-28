<?php
/**
 * Test class for McpWooProducts
 *
 * @package Automattic\WordpressMcp\Tests\Tools
 */

namespace Automattic\WordpressMcp\Tests\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Tools\McpWooProducts;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;
use WC_Product_Simple;
use WC_Product_Grouped;
use WC_Product_External;
use WC_Product_Variable;

/**
 * Test class for McpWooProducts
 */
final class McpWooProductsTest extends WP_UnitTestCase {

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
	 * Test the wc_products_search tool.
	 */
	public function test_wc_products_search_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create some test products.
		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Test Simple Product' );
		$simple_product->set_regular_price( 100 );
		$simple_product->save();

		$grouped_product = new WC_Product_Grouped();
		$grouped_product->set_name( 'Test Grouped Product' );
		$grouped_product->save();

		$external_product = new WC_Product_External();
		$external_product->set_name( 'Test External Product' );
		$external_product->set_product_url( 'https://example.com' );
		$external_product->set_button_text( 'Buy Now' );
		$external_product->save();

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
					'name'   => 'wc_products_search',
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

		// Verify we got all products.
		$this->assertCount( 4, $response_json );
	}

	/**
	 * Test the wc_get_product tool.
	 */
	public function test_wc_get_product_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 100 );
		$product->save();

		$product_id = $product->get_id();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_get_product',
					'arguments' => array(
						'id' => $product_id,
					),
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

		// Verify product data.
		$this->assertEquals( 'Test Product', $response_json['name'] );
		$this->assertEquals( '100', $response_json['regular_price'] );
		$this->assertEquals( 'simple', $response_json['type'] );
	}

	/**
	 * Test the wc_add_product tool.
	 */
	public function test_wc_add_product_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_add_product',
					'arguments' => array(
						'name'          => 'New Test Product',
						'type'          => 'simple',
						'regular_price' => '150',
						'description'   => 'Test product description',
					),
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

		// Verify product was created.
		$this->assertEquals( 'New Test Product', $response_json['name'] );
		$this->assertEquals( '150', $response_json['regular_price'] );
		$this->assertEquals( 'simple', $response_json['type'] );
		$this->assertEquals( 'Test product description', $response_json['description'] );
	}

	/**
	 * Test the wc_update_product tool.
	 */
	public function test_wc_update_product_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Original Product' );
		$product->set_regular_price( 100 );
		$product->save();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_update_product',
					'arguments' => array(
						'id'            => $product->get_id(),
						'name'          => 'Updated Product',
						'regular_price' => '200',
					),
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

		// Verify product was updated.
		$this->assertEquals( 'Updated Product', $response_json['name'] );
		$this->assertEquals( '200', $response_json['regular_price'] );
	}

	/**
	 * Test the wc_delete_product tool.
	 */
	public function test_wc_delete_product_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Product to Delete' );
		$product->set_regular_price( 100 );
		$product->save();

		$product_id = $product->get_id();

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_delete_product',
					'arguments' => array(
						'id' => $product_id,
					),
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		$product = wc_get_product( $product_id );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );

		// Verify product was deleted.
		$this->assertEquals( 'trash', $product->get_status() );
	}
}

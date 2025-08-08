<?php
/**
 * Test class for McpWooAttributes
 *
 * @package Automattic\WordpressMcp\Tests\Tools
 */

namespace Automattic\WordpressMcp\Tests\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Tools\McpWooAttributes;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;

/**
 * Test class for McpWooAttributes
 */
final class McpWooAttributesTest extends WP_UnitTestCase {

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
		do_action( 'wordpress_mcp_init' );
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
	 * Test the wc_get_product_attributes tool.
	 */
	public function test_wc_get_product_attributes_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test attribute.
		$attribute = wc_create_attribute( array(
			'name' => 'Test Color',
			'slug' => 'test-color',
			'type' => 'select',
			'order_by' => 'menu_order',
			'has_archives' => false,
		) );

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wc_get_product_attributes',
				)
			)
		);

		// Set content type header.
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );

		// Clean up.
		wc_delete_attribute( $attribute['id'] );
	}

	/**
	 * Test the wc_get_product_attribute tool.
	 */
	public function test_wc_get_product_attribute_tool(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test attribute.
		$attribute = wc_create_attribute( array(
			'name' => 'Test Size',
			'slug' => 'test-size',
			'type' => 'select',
			'order_by' => 'menu_order',
			'has_archives' => false,
		) );

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_get_product_attribute',
					'arguments' => array(
						'id' => $attribute['id'],
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

		// Verify attribute data.
		$this->assertEquals( 'Test Size', $response_json['name'] );
		$this->assertEquals( 'test-size', $response_json['slug'] );

		// Clean up.
		wc_delete_attribute( $attribute['id'] );
	}

	/**
	 * Test the wc_get_attribute_terms tool with attribute_id.
	 */
	public function test_wc_get_attribute_terms_with_attribute_id(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test attribute.
		$attribute = wc_create_attribute( array(
			'name' => 'Test Material',
			'slug' => 'test-material',
			'type' => 'select',
			'order_by' => 'menu_order',
			'has_archives' => false,
		) );

		// Create some terms for the attribute.
		wp_insert_term( 'Cotton', 'pa_test-material' );
		wp_insert_term( 'Silk', 'pa_test-material' );

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_get_attribute_terms',
					'arguments' => array(
						'attribute_id' => $attribute['id'],
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

		// Verify we got the terms.
		$this->assertIsArray( $response_json );
		$this->assertGreaterThanOrEqual( 2, count( $response_json ) );

		// Clean up.
		wc_delete_attribute( $attribute['id'] );
	}

	/**
	 * Test the wc_get_attribute_terms tool with attribute_name.
	 */
	public function test_wc_get_attribute_terms_with_attribute_name(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test attribute.
		$attribute = wc_create_attribute( array(
			'name' => 'Test Fabric',
			'slug' => 'test-fabric',
			'type' => 'select',
			'order_by' => 'menu_order',
			'has_archives' => false,
		) );

		// Create some terms for the attribute.
		wp_insert_term( 'Denim', 'pa_test-fabric' );
		wp_insert_term( 'Wool', 'pa_test-fabric' );

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_get_attribute_terms',
					'arguments' => array(
						'attribute_name' => 'test-fabric',
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

		// Verify we got the terms.
		$this->assertIsArray( $response_json );
		$this->assertGreaterThanOrEqual( 2, count( $response_json ) );

		// Clean up.
		wc_delete_attribute( $attribute['id'] );
	}

	/**
	 * Test the wc_get_attribute_terms tool with query parameter.
	 */
	public function test_wc_get_attribute_terms_with_query(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test attribute.
		$attribute = wc_create_attribute( array(
			'name' => 'Test Style',
			'slug' => 'test-style',
			'type' => 'select',
			'order_by' => 'menu_order',
			'has_archives' => false,
		) );

		// Create some terms for the attribute.
		wp_insert_term( 'Casual', 'pa_test-style' );
		wp_insert_term( 'Formal', 'pa_test-style' );

		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_get_attribute_terms',
					'arguments' => array(
						'query' => 'test-style',
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

		// Verify we got the terms.
		$this->assertIsArray( $response_json );
		$this->assertGreaterThanOrEqual( 2, count( $response_json ) );

		// Clean up.
		wc_delete_attribute( $attribute['id'] );
	}

	/**
	 * Test the wc_get_attribute_terms tool with no parameters.
	 */
	public function test_wc_get_attribute_terms_with_no_parameters(): void {
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
					'name'      => 'wc_get_attribute_terms',
					'arguments' => array(),
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

		// Verify we got an error.
		$this->assertIsArray( $response_json );
		$this->assertArrayHasKey( 'error', $response_json );
		$this->assertEquals( 'Either attribute_id or attribute_name/query parameter is required', $response_json['error'] );
	}

	/**
	 * Test the wc_get_attribute_terms tool with invalid attribute_name.
	 */
	public function test_wc_get_attribute_terms_with_invalid_attribute_name(): void {
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
					'name'      => 'wc_get_attribute_terms',
					'arguments' => array(
						'attribute_name' => 'nonexistent-attribute',
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

		// Verify we got an error.
		$this->assertIsArray( $response_json );
		$this->assertArrayHasKey( 'error', $response_json );
		$this->assertStringContainsString( 'Attribute \'nonexistent-attribute\' not found', $response_json['error'] );
	}

	/**
	 * Test the wc_get_attribute_terms tool with case insensitive matching.
	 */
	public function test_wc_get_attribute_terms_case_insensitive(): void {
		// Skip test if WooCommerce is not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is not active.' );
		}

		// Create a test attribute.
		$attribute = wc_create_attribute( array(
			'name' => 'Test Pattern',
			'slug' => 'test-pattern',
			'type' => 'select',
			'order_by' => 'menu_order',
			'has_archives' => false,
		) );

		// Create some terms for the attribute.
		wp_insert_term( 'Striped', 'pa_test-pattern' );
		wp_insert_term( 'Solid', 'pa_test-pattern' );

		// Create a REST request with uppercase attribute name.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wc_get_attribute_terms',
					'arguments' => array(
						'attribute_name' => 'TEST-PATTERN',
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

		// Verify we got the terms (case insensitive match should work).
		$this->assertIsArray( $response_json );
		$this->assertGreaterThanOrEqual( 2, count( $response_json ) );

		// Clean up.
		wc_delete_attribute( $attribute['id'] );
	}

	/**
	 * Test the wc_get_attribute_terms tool with invalid attribute_id.
	 */
	public function test_wc_get_attribute_terms_with_invalid_attribute_id(): void {
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
					'name'      => 'wc_get_attribute_terms',
					'arguments' => array(
						'attribute_id' => 999999,
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

		// Verify we got an error.
		$this->assertIsArray( $response_json );
		$this->assertArrayHasKey( 'error', $response_json );
		$this->assertStringContainsString( 'woocommerce_rest_taxonomy_invalid', $response_json['error'] );
	}
}

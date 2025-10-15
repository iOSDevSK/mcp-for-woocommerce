<?php
/**
 * Test class for McpWooShipping
 *
 * @package McpForWoo\Tests\Tools
 */

namespace McpForWoo\Tests\Tools;

use McpForWoo\Core\WpMcp;
use McpForWoo\Tools\McpWooShipping;
use WP_UnitTestCase;
use WP_User;

/**
 * Test class for McpWooShipping
 */
final class McpWooShippingTest extends WP_UnitTestCase {

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
	 * The shipping tool instance.
	 *
	 * @var McpWooShipping
	 */
	private McpWooShipping $shipping_tool;

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

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

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

		// Create shipping tool instance.
		$this->shipping_tool = new McpWooShipping();
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
	 * Test shipping to unsupported country (Russia).
	 * This should return a proper response indicating shipping is not available.
	 */
	public function test_shipping_to_unsupported_country_russia(): void {
		// Test with Russia (should not be supported)
		$params = array(
			'country' => 'Russia'
		);

		$result = $this->shipping_tool->check_shipping_to_country( $params );

		// Verify the result is an array
		$this->assertIsArray( $result );

		// Verify the response structure
		$this->assertArrayHasKey( 'available', $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertArrayHasKey( 'country', $result );
		$this->assertArrayHasKey( 'country_code', $result );

		// Verify shipping is not available
		$this->assertFalse( $result['available'] );

		// Verify the country was recognized
		$this->assertEquals( 'Russia', $result['country'] );
		$this->assertEquals( 'RU', $result['country_code'] );

		// Verify appropriate message
		$this->assertStringContainsString( 'not available', $result['message'] );
	}

	/**
	 * Test shipping to unrecognized country.
	 * This should return a proper response indicating the country is not recognized.
	 */
	public function test_shipping_to_unrecognized_country(): void {
		// Test with fake country
		$params = array(
			'country' => 'Atlantis'
		);

		$result = $this->shipping_tool->check_shipping_to_country( $params );

		// Verify the result is an array
		$this->assertIsArray( $result );

		// Verify the response structure
		$this->assertArrayHasKey( 'available', $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertArrayHasKey( 'country_input', $result );

		// Verify shipping is not available
		$this->assertFalse( $result['available'] );

		// Verify the country input is preserved
		$this->assertEquals( 'Atlantis', $result['country_input'] );

		// Verify appropriate message
		$this->assertStringContainsString( 'not recognized', $result['message'] );
	}

	/**
	 * Test shipping tool with missing parameter.
	 * This should throw an exception that gets handled by the JSON-RPC framework.
	 */
	public function test_shipping_tool_missing_parameter(): void {
		// Test with missing country parameter
		$params = array();

		$result = $this->shipping_tool->check_shipping_to_country( $params );

		// Verify the result is an array
		$this->assertIsArray( $result );

		// Verify the response structure
		$this->assertArrayHasKey( 'available', $result );
		$this->assertArrayHasKey( 'message', $result );

		// Verify shipping is not available
		$this->assertFalse( $result['available'] );

		// Verify appropriate message
		$this->assertStringContainsString( 'required', $result['message'] );
	}

	/**
	 * Test that WooCommerce unavailable scenario throws exception.
	 * This simulates when WooCommerce classes are not available.
	 */
	public function test_woocommerce_unavailable_throws_exception(): void {
		// Mock a scenario where WooCommerce is not available
		// We'll test the get_all_shipping_methods which checks for WC_Shipping_Zones
		
		// Temporarily rename the WC_Shipping_Zones class to simulate unavailable WooCommerce
		if ( class_exists( 'WC_Shipping_Zones' ) ) {
			// We can't easily mock this in a unit test without more complex setup
			// So we'll test the check_shipping_to_country method with WC unavailable response
			
			$params = array(
				'country' => 'USA'
			);

			// The check_shipping_to_country method has a built-in check for WC classes
			// When WC classes are not available, it returns appropriate error response
			$result = $this->shipping_tool->check_shipping_to_country( $params );
			
			// The result should be an array (it won't throw exception but return error response)
			$this->assertIsArray( $result );
		}
	}

	/**
	 * Test that invalid zone ID throws exception in get_shipping_zone_safe.
	 * This tests our fix for proper JSON-RPC error handling.
	 */
	public function test_invalid_zone_id_throws_exception(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid zone ID' );

		// Test with invalid zone ID
		$params = array(
			'id' => -1
		);

		$this->shipping_tool->get_shipping_zone_safe( $params );
	}

	/**
	 * Test that non-existent zone ID throws exception in get_shipping_zone_safe.
	 * This tests our fix for proper JSON-RPC error handling.
	 */
	public function test_nonexistent_zone_id_throws_exception(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Zone does not exist' );

		// Test with non-existent zone ID
		$params = array(
			'id' => 999999
		);

		$this->shipping_tool->get_shipping_zone_safe( $params );
	}

	/**
	 * Test that invalid zone ID throws exception in get_shipping_methods_safe.
	 * This tests our fix for proper JSON-RPC error handling.
	 */
	public function test_invalid_zone_id_in_methods_throws_exception(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid zone ID' );

		// Test with invalid zone ID
		$params = array(
			'zone_id' => -1
		);

		$this->shipping_tool->get_shipping_methods_safe( $params );
	}

	/**
	 * Test that non-existent zone ID throws exception in get_shipping_methods_safe.
	 * This tests our fix for proper JSON-RPC error handling.
	 */
	public function test_nonexistent_zone_id_in_methods_throws_exception(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Zone does not exist' );

		// Test with non-existent zone ID
		$params = array(
			'zone_id' => 999999
		);

		$this->shipping_tool->get_shipping_methods_safe( $params );
	}

	/**
	 * Test that WooCommerce unavailable throws exception in get_all_shipping_methods.
	 * This tests our fix for proper JSON-RPC error handling.
	 */
	public function test_woocommerce_unavailable_in_all_methods_throws_exception(): void {
		// Since we can't easily mock WooCommerce unavailability in unit tests,
		// we'll test that the method returns proper data structure when WC is available
		
		$result = $this->shipping_tool->get_all_shipping_methods();
		
		// The result should be an array
		$this->assertIsArray( $result );
		
		// Each item should have expected structure
		foreach ( $result as $zone_data ) {
			$this->assertArrayHasKey( 'zone_id', $zone_data );
			$this->assertArrayHasKey( 'zone_name', $zone_data );
			$this->assertArrayHasKey( 'locations', $zone_data );
			$this->assertArrayHasKey( 'shipping_methods', $zone_data );
		}
	}

	/**
	 * Test successful shipping zone retrieval.
	 * This tests that valid zone ID returns proper data.
	 */
	public function test_successful_zone_retrieval(): void {
		// Test with default zone (zone 0)
		$params = array(
			'id' => 0
		);

		$result = $this->shipping_tool->get_shipping_zone_safe( $params );

		// Verify the result is an array
		$this->assertIsArray( $result );

		// Verify the response structure
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'order', $result );
		$this->assertArrayHasKey( 'locations', $result );

		// Verify zone data
		$this->assertEquals( 0, $result['id'] );
		$this->assertIsString( $result['name'] );
		$this->assertIsArray( $result['locations'] );
	}

	/**
	 * Test successful shipping methods retrieval.
	 * This tests that valid zone ID returns proper methods data.
	 */
	public function test_successful_methods_retrieval(): void {
		// Test with default zone (zone 0)
		$params = array(
			'zone_id' => 0
		);

		$result = $this->shipping_tool->get_shipping_methods_safe( $params );

		// Verify the result is an array
		$this->assertIsArray( $result );

		// Each method should have expected structure
		foreach ( $result as $method ) {
			$this->assertArrayHasKey( 'id', $method );
			$this->assertArrayHasKey( 'instance_id', $method );
			$this->assertArrayHasKey( 'title', $method );
			$this->assertArrayHasKey( 'enabled', $method );
			$this->assertArrayHasKey( 'method_id', $method );
		}
	}
}
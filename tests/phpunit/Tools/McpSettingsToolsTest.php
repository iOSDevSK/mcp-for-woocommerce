<?php
/**
 * Test class for McpSettingsTools
 *
 * @package Automattic\WordpressMcp\Tests\Tools
 */

namespace Automattic\WordpressMcp\Tests\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Tools\McpSettingsTools;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;

/**
 * Test class for McpSettingsTools
 */
final class McpSettingsToolsTest extends WP_UnitTestCase {

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

		// Initialize the REST API and MCP.
		do_action( 'rest_api_init' );
	}

	/**
	 * Test the wp_get_general_settings tool.
	 */
	public function test_wp_get_general_settings_tool(): void {
		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wp_get_general_settings',
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
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );

		// Get the settings from the response.
		$settings = json_decode( $response->get_data()['content'][0]['text'], true );

		// Assert that we have some basic settings.
		$this->assertArrayHasKey( 'title', $settings );
		// Assert that we have some basic settings.
		$this->assertArrayHasKey( 'title', $settings );
		$this->assertArrayHasKey( 'description', $settings );
		$this->assertArrayHasKey( 'timezone', $settings );
		$this->assertArrayHasKey( 'date_format', $settings );
		$this->assertArrayHasKey( 'time_format', $settings );
		$this->assertArrayHasKey( 'start_of_week', $settings );
		$this->assertArrayHasKey( 'language', $settings );
	}

	/**
	 * Test the wp_update_general_settings tool.
	 */
	public function test_wp_update_general_settings_tool(): void {
		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method'    => 'tools/call',
					'name'      => 'wp_update_general_settings',
					'arguments' => array(
						'title'         => 'Updated Site Title',
						'description'   => 'Updated Site Description',
						'timezone'      => 'America/New_York',
						'date_format'   => 'F j, Y',
						'time_format'   => 'g:i a',
						'start_of_week' => 1,
						'language'      => 'en_US',
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

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertIsArray( $response->get_data()['content'] );
		$this->assertCount( 1, $response->get_data()['content'] );
		$this->assertEquals( 'text', $response->get_data()['content'][0]['type'] );

		// Get the updated settings from the response.
		$settings = json_decode( $response->get_data()['content'][0]['text'], true );

		// Assert that the settings were updated correctly.
		$this->assertEquals( 'Updated Site Title', $settings['title'] );
		$this->assertEquals( 'Updated Site Description', $settings['description'] );
		$this->assertEquals( 'America/New_York', $settings['timezone'] );
		$this->assertEquals( 'F j, Y', $settings['date_format'] );
		$this->assertEquals( 'g:i a', $settings['time_format'] );
		$this->assertEquals( 1, $settings['start_of_week'] );
		$this->assertEquals( 'en_US', $settings['language'] );
	}
}

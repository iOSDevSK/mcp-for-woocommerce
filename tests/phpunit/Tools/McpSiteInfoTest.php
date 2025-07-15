<?php
/**
 * Test class for McpSiteInfo
 *
 * @package Automattic\WordpressMcp\Tests\Tools
 */

namespace Automattic\WordpressMcp\Tests\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Tools\McpSiteInfo;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;

/**
 * Test class for McpSiteInfo
 */
final class McpSiteInfoTest extends WP_UnitTestCase {

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
	 * Test the get_site_info tool.
	 */
	public function test_get_site_info_tool(): void {
		// Create a REST request.
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );

		// Set the request body as JSON.
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'get_site_info',
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

		// Get the site info from the response.
		$site_info = json_decode( $response->get_data()['content'][0]['text'], true );

		// Assert that we have all the expected site info fields.
		$this->assertArrayHasKey( 'site_name', $site_info );
		$this->assertArrayHasKey( 'site_url', $site_info );
		$this->assertArrayHasKey( 'site_description', $site_info );
		$this->assertArrayHasKey( 'site_admin_email', $site_info );
		$this->assertArrayHasKey( 'plugins', $site_info );
		$this->assertArrayHasKey( 'themes', $site_info );
		$this->assertArrayHasKey( 'users', $site_info );

		// Assert themes structure
		$this->assertArrayHasKey( 'active', $site_info['themes'] );
		$this->assertArrayHasKey( 'all', $site_info['themes'] );

		// Assert that the site info contains valid data
		$this->assertNotEmpty( $site_info['site_name'] );
		$this->assertNotEmpty( $site_info['site_url'] );
		$this->assertNotEmpty( $site_info['site_admin_email'] );
		$this->assertIsArray( $site_info['plugins'] );
		$this->assertIsArray( $site_info['themes']['active'] );
		$this->assertIsArray( $site_info['themes']['all'] );
		$this->assertIsArray( $site_info['users'] );
	}
}

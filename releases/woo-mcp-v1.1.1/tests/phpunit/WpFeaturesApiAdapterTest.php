<?php

namespace Automattic\WordpressMcp\Tests;

use Automattic\WordpressMcp\Core\WpFeaturesAdapter;
use WP_UnitTestCase;
use WP_User;
use WP_REST_Server;

/**
 * Test class for WpFeaturesAdapter
 *
 * @package Automattic\WordpressMcp\Tests
 */
class WpFeaturesApiAdapterTest extends WP_UnitTestCase {

	/**
	 * The adapter instance.
	 *
	 * @var WpFeaturesAdapter
	 */
	private WpFeaturesAdapter $adapter;

	/**
	 * The admin user.
	 *
	 * @var WP_User
	 */
	private WP_User $admin_user;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::set_up();

		// Create an admin user.
		$this->admin_user = $this->factory->user->create_and_get(
			array(
				'role' => 'administrator',
			)
		);

		// Check if the Feature API is available.
		if ( ! function_exists( 'wp_register_feature' ) ) {
			$this->markTestSkipped( 'WordPress Feature API is not available.' );
		}

		// Mock the REST server.
		do_action( 'rest_api_init' );
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Reset the REST server.
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Test that features are properly registered as MCP tools.
	 */
	public function test_features_registered_as_mcp_tools(): void {

		add_action(
			'wp_feature_api_init',
			function () {
				wp_register_feature(
					array(
						'id'                  => 'test-feature',
						'name'                => 'Test Feature',
						'description'         => 'A test feature for unit testing',
						'type'                => 'resource',
						'categories'          => array( 'test' ),
						'callback'            => function ( $args ) {
							return array(
								'message' => 'Hello ' . ( $args['name'] ?? 'World' ),
							);
						},
						'input_schema'        => array(
							'type'       => 'object',
							'properties' => array(
								'name' => array( 'type' => 'string' ),
							),
						),
						'output_schema'       => array(
							'type'       => 'object',
							'properties' => array(
								'message' => array( 'type' => 'string' ),
							),
						),
						'permission_callback' => function () {
							return current_user_can( 'manage_options' );
						},
					)
				);
			}
		);

		// Initialize the adapter.
		do_action( 'wordpress_mcp_init' );

		// Create a REST request to list tools.
		$request = new \WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/list',
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user.
		wp_set_current_user( $this->admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'tools', $response->get_data() );
		$this->assertIsArray( $response->get_data()['tools'] );

		// Find our test feature in the tools list.
		$test_tool = null;
		foreach ( $response->get_data()['tools'] as $tool ) {
			if ( 'wp_feature_test-feature' === $tool['name'] ) {
				$test_tool = $tool;
				break;
			}
		}

		// Verify the tool was registered correctly.
		$this->assertNotNull( $test_tool, 'Test feature was not registered as an MCP tool' );
		$this->assertEquals( 'A test feature for unit testing', $test_tool['description'] );
		$this->assertEquals( 'read', $test_tool['type'] );
		$this->assertArrayHasKey( 'inputSchema', $test_tool );
		$this->assertArrayHasKey( 'outputSchema', $test_tool );
	}

	/**
	 * Test that feature callbacks work through MCP tools.
	 */
	public function test_feature_callback_through_mcp_tool(): void {
		// Initialize the adapter.
		do_action( 'wordpress_mcp_init' );

		// Create a REST request to call the tool.
		$request = new \WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wp_feature_test-feature',
					'args'   => array(
						'name' => 'Test User',
					),
				)
			)
		);
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
		$this->assertEquals( '{"message":"Hello Test User"}', $response->get_data()['content'][0]['text'] );
	}

	/**
	 * Test that feature permissions are enforced.
	 */
	public function test_feature_permissions_enforced(): void {
		// Initialize the adapter.
		do_action( 'wordpress_mcp_init' );

		// Create a non-admin user.
		$non_admin_user = $this->factory->user->create_and_get(
			array(
				'role' => 'subscriber',
			)
		);

		// Create a REST request to call the tool.
		$request = new \WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$request->set_body(
			wp_json_encode(
				array(
					'method' => 'tools/call',
					'name'   => 'wp_feature_test-feature',
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		// Set the current user to non-admin.
		wp_set_current_user( $non_admin_user->ID );

		// Dispatch the request.
		$response = rest_do_request( $request );

		// Check the response.
		$this->assertEquals( 403, $response->get_status() );
		$this->assertArrayHasKey( 'code', $response->get_data() );
		$this->assertEquals( 'rest_forbidden', $response->get_data()['code'] );
	}
}

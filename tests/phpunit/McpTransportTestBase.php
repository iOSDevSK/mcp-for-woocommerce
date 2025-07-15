<?php
/**
 * Base test class for MCP Transport testing.
 *
 * @package WordPressMcp
 * @subpackage Tests
 */

namespace Automattic\WordpressMcp\Tests;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Auth\JwtAuth;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;

/**
 * Abstract base class for MCP transport tests.
 * Provides common functionality and defines the interface for transport-specific tests.
 */
abstract class McpTransportTestBase extends WP_UnitTestCase {

	/**
	 * The WordPress MCP instance.
	 *
	 * @var WpMcp
	 */
	protected WpMcp $wp_mcp;

	/**
	 * The JWT Auth instance.
	 *
	 * @var JwtAuth
	 */
	protected JwtAuth $jwt_auth;

	/**
	 * Administrator user for testing.
	 *
	 * @var WP_User
	 */
	protected WP_User $admin_user;

	/**
	 * Editor user for testing.
	 *
	 * @var WP_User
	 */
	protected WP_User $editor_user;

	/**
	 * Subscriber user for testing.
	 *
	 * @var WP_User
	 */
	protected WP_User $subscriber_user;

	/**
	 * Generated JWT token for admin user.
	 *
	 * @var string
	 */
	protected string $admin_jwt_token = '';

	/**
	 * Generated JWT token for editor user.
	 *
	 * @var string
	 */
	protected string $editor_jwt_token = '';

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable MCP functionality
		update_option( 'wordpress_mcp_settings', array( 'enabled' => true ) );
		
		// Ensure Application Passwords are available for testing
		if ( ! wp_is_application_passwords_available() ) {
			// Force enable Application Passwords for testing
			add_filter( 'wp_is_application_passwords_available', '__return_true' );
		}
		
		// Force HTTPS for Application Passwords (required in many WordPress configurations)
		$_SERVER['HTTPS'] = 'on';
		$_SERVER['SERVER_PORT'] = '443';

		// Create test users with different roles
		$this->admin_user = $this->factory->user->create_and_get(
			array(
				'role'      => 'administrator',
				'user_pass' => 'test_password',
				'user_login' => 'test_admin',
			)
		);
		// Ensure Application Passwords are enabled for this user
		update_user_meta( $this->admin_user->ID, '_application_passwords_enabled', true );

		$this->editor_user = $this->factory->user->create_and_get(
			array(
				'role'      => 'editor',
				'user_pass' => 'test_password',
				'user_login' => 'test_editor',
			)
		);
		// Ensure Application Passwords are enabled for this user
		update_user_meta( $this->editor_user->ID, '_application_passwords_enabled', true );

		$this->subscriber_user = $this->factory->user->create_and_get(
			array(
				'role'      => 'subscriber',
				'user_pass' => 'test_password',
				'user_login' => 'test_subscriber',
			)
		);
		// Ensure Application Passwords are enabled for this user
		update_user_meta( $this->subscriber_user->ID, '_application_passwords_enabled', true );

		// Initialize MCP and JWT Auth
		$this->wp_mcp = new WpMcp();
		$this->jwt_auth = new JwtAuth();

		// Initialize REST API
		do_action( 'rest_api_init' );

		// Explicitly trigger MCP initialization (which happens at high priority during rest_api_init)
		$this->wp_mcp->wordpress_mcp_init();

		// Generate JWT tokens for testing
		$this->admin_jwt_token = $this->generate_jwt_token_for_user( $this->admin_user->ID );
		$this->editor_jwt_token = $this->generate_jwt_token_for_user( $this->editor_user->ID );

		// Clean up server globals
		$this->clean_server_globals();
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown(): void {
		// Clean up options
		delete_option( 'wordpress_mcp_settings' );
		delete_option( 'jwt_token_registry' );
		delete_option( 'wpmcp_jwt_secret_key' );

		// Clean up server globals
		$this->clean_server_globals();

		parent::tearDown();
	}

	/**
	 * Abstract method to get the transport endpoint.
	 *
	 * @return string The REST API endpoint for this transport.
	 */
	abstract protected function get_transport_endpoint(): string;

	/**
	 * Abstract method to create a valid request for this transport.
	 *
	 * @param string $method The MCP method to call.
	 * @param array  $params The parameters for the method.
	 * @param array  $headers Additional headers to include.
	 * @return WP_REST_Request
	 */
	abstract protected function create_transport_request( string $method, array $params = array(), array $headers = array() ): WP_REST_Request;

	/**
	 * Abstract method to assert a valid response for this transport.
	 *
	 * @param mixed $response The response to validate.
	 * @param array $expected_data Expected data in the response.
	 */
	abstract protected function assert_valid_response( $response, array $expected_data = array() ): void;

	/**
	 * Abstract method to assert an error response for this transport.
	 *
	 * @param mixed  $response The response to validate.
	 * @param string $expected_error_code Expected error code.
	 * @param int    $expected_status Expected HTTP status code.
	 */
	abstract protected function assert_error_response( $response, string $expected_error_code, int $expected_status ): void;

	/**
	 * Generate a JWT token for a specific user.
	 *
	 * @param int $user_id The user ID.
	 * @return string The JWT token.
	 */
	protected function generate_jwt_token_for_user( int $user_id ): string {
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$data = $response->get_data();

		return $data['token'] ?? '';
	}

	/**
	 * Set JWT authentication for the current request.
	 *
	 * @param string $token The JWT token to use.
	 */
	protected function set_jwt_auth( string $token ): void {
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$_SERVER['REQUEST_URI'] = '/wp-json' . $this->get_transport_endpoint();
		
		// Manually trigger JWT authentication to set current user
		$auth_result = $this->jwt_auth->authenticate_request( null );
		if ( ! is_wp_error( $auth_result ) ) {
			// Authentication succeeded, current user should now be set
		}
	}

	/**
	 * Set application password authentication for the current request.
	 *
	 * @param string $username The username.
	 * @param string $password The application password.
	 */
	protected function set_application_password_auth( string $username, string $password ): void {
		$_SERVER['PHP_AUTH_USER'] = $username;
		$_SERVER['PHP_AUTH_PW'] = $password;
		$_SERVER['REQUEST_URI'] = '/wp-json' . $this->get_transport_endpoint();
	}

	/**
	 * Clear all authentication for the current request.
	 */
	protected function clear_auth(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		unset( $_SERVER['PHP_AUTH_USER'] );
		unset( $_SERVER['PHP_AUTH_PW'] );
		wp_set_current_user( 0 );
	}

	/**
	 * Clean up server globals that might affect tests.
	 */
	protected function clean_server_globals(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		unset( $_SERVER['PHP_AUTH_USER'] );
		unset( $_SERVER['PHP_AUTH_PW'] );
		unset( $_SERVER['REQUEST_URI'] );
		unset( $_SERVER['HTTP_ACCEPT'] );
		unset( $_SERVER['CONTENT_TYPE'] );
	}

	/**
	 * Create an application password for a user.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $name The application password name.
	 * @return array Array with 'password' and 'uuid' keys.
	 */
	protected function create_application_password( int $user_id, string $name = 'Test App' ): array {
		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			$this->markTestSkipped( 'Application Passwords not available' );
		}

		$created = \WP_Application_Passwords::create_new_application_password( $user_id, array( 'name' => $name ) );
		
		if ( is_wp_error( $created ) ) {
			$this->fail( 'Failed to create application password: ' . $created->get_error_message() );
		}

		// WordPress returns array( $password, $item_details )
		// Transform to expected format for our tests
		return array(
			'password' => $created[0], // Plain text password
			'uuid'     => $created[1]['uuid'], // UUID from item details
		);
	}

	/**
	 * Test that routes are registered correctly.
	 */
	public function test_transport_routes_registered(): void {
		$routes = rest_get_server()->get_routes();
		$endpoint = $this->get_transport_endpoint();
		
		$this->assertArrayHasKey( $endpoint, $routes, "Transport endpoint {$endpoint} should be registered" );
	}

	/**
	 * Test MCP is enabled check.
	 */
	public function test_mcp_disabled_blocks_access(): void {
		// Disable MCP
		update_option( 'wordpress_mcp_settings', array( 'enabled' => false ) );

		// Set up authentication
		$this->set_jwt_auth( $this->admin_jwt_token );

		// Try to make a request
		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );

		$this->assert_error_response( $response, 'mcp_disabled', 403 );
	}

	/**
	 * Test successful ping request with proper authentication.
	 */
	public function test_successful_ping_request(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );

		$this->assert_valid_response( $response );
	}

	/**
	 * Test request with invalid method.
	 */
	public function test_invalid_method_request(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		$request = $this->create_transport_request( 'invalid_method_name' );
		$response = rest_do_request( $request );

		// For JSON-RPC 2.0 (Streamable), method errors return HTTP 200 with error in body
		// For STDIO, it should return HTTP 400
		$status = $response->get_status();
		if ( 200 === $status ) {
			// JSON-RPC 2.0 format - check for error in response body
			$this->assertEquals( 200, $status, 'JSON-RPC 2.0 should return 200 for method errors' );
			$data = $response->get_data();
			$this->assertArrayHasKey( 'error', $data, 'Response should contain error field' );
		} else {
			// WordPress REST format - should be 400
			$this->assert_error_response( $response, 'invalid_method', 400 );
		}
	}

	/**
	 * Test request without authentication.
	 */
	public function test_request_without_authentication(): void {
		$this->clear_auth();

		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );

		// Both transports should reject unauthenticated requests
		$this->assertTrue( 
			in_array( $response->get_status(), array( 401, 403 ), true ),
			'Unauthenticated requests should be rejected with 401 or 403'
		);
	}

	/**
	 * Data provider for different user roles.
	 *
	 * @return array
	 */
	public function user_role_provider(): array {
		return array(
			'administrator' => array( 'admin' ),
			'editor'        => array( 'editor' ),
			'subscriber'    => array( 'subscriber' ),
		);
	}

	/**
	 * Data provider for common MCP methods to test.
	 *
	 * @return array
	 */
	public function mcp_method_provider(): array {
		return array(
			'ping'              => array( 'ping', array() ),
			'initialize'        => array( 'initialize', array( 'protocolVersion' => '2024-11-05' ) ),
			'tools/list'        => array( 'tools/list', array() ),
			'resources/list'    => array( 'resources/list', array() ),
		);
	}
} 
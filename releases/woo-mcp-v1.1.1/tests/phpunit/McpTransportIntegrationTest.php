<?php
/**
 * Integration tests comparing STDIO and Streamable transports.
 *
 * @package WordPressMcp
 * @subpackage Tests
 */

namespace Automattic\WordpressMcp\Tests;

use Automattic\WordpressMcp\Core\McpStdioTransport;
use Automattic\WordpressMcp\Core\McpStreamableTransport;
use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Auth\JwtAuth;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;

/**
 * Integration test cases comparing both MCP transports.
 * Tests differences in authentication, response formats, and behavior.
 */
class McpTransportIntegrationTest extends WP_UnitTestCase {

	/**
	 * The WordPress MCP instance.
	 *
	 * @var WpMcp
	 */
	private WpMcp $wp_mcp;

	/**
	 * The JWT Auth instance.
	 *
	 * @var JwtAuth
	 */
	private JwtAuth $jwt_auth;

	/**
	 * The STDIO transport instance.
	 *
	 * @var McpStdioTransport
	 */
	private McpStdioTransport $stdio_transport;

	/**
	 * The Streamable transport instance.
	 *
	 * @var McpStreamableTransport
	 */
	private McpStreamableTransport $streamable_transport;

	/**
	 * Administrator user for testing.
	 *
	 * @var WP_User
	 */
	private WP_User $admin_user;

	/**
	 * Editor user for testing.
	 *
	 * @var WP_User
	 */
	private WP_User $editor_user;

	/**
	 * Generated JWT token for admin user.
	 *
	 * @var string
	 */
	private string $admin_jwt_token = '';

	/**
	 * Generated JWT token for editor user.
	 *
	 * @var string
	 */
	private string $editor_jwt_token = '';

	/**
	 * Set up the integration test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable MCP functionality
		update_option( 'wordpress_mcp_settings', array( 'enabled' => true ) );

		// Create test users
		$this->admin_user = $this->factory->user->create_and_get(
			array(
				'role'      => 'administrator',
				'user_pass' => 'test_password',
				'user_login' => 'test_admin_integration',
			)
		);

		$this->editor_user = $this->factory->user->create_and_get(
			array(
				'role'      => 'editor',
				'user_pass' => 'test_password',
				'user_login' => 'test_editor_integration',
			)
		);

		// Initialize MCP and transports
		$this->wp_mcp = new WpMcp();
		$this->jwt_auth = new JwtAuth();
		$this->stdio_transport = new McpStdioTransport( $this->wp_mcp );
		$this->streamable_transport = new McpStreamableTransport( $this->wp_mcp );

		// Initialize REST API
		do_action( 'rest_api_init' );

		// Explicitly trigger MCP initialization (which happens at high priority during rest_api_init)
		$this->wp_mcp->wordpress_mcp_init();

		// Generate JWT tokens
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
	 * Generate a JWT token for a specific user.
	 *
	 * @param int $user_id The user ID.
	 * @return string The JWT token.
	 */
	private function generate_jwt_token_for_user( int $user_id ): string {
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$data = $response->get_data();

		return $data['token'] ?? '';
	}

	/**
	 * Clean up server globals that might affect tests.
	 */
	private function clean_server_globals(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		unset( $_SERVER['PHP_AUTH_USER'] );
		unset( $_SERVER['PHP_AUTH_PW'] );
		unset( $_SERVER['REQUEST_URI'] );
		unset( $_SERVER['HTTP_ACCEPT'] );
		unset( $_SERVER['CONTENT_TYPE'] );
	}

	/**
	 * Set JWT authentication for the current request.
	 *
	 * @param string $token The JWT token to use.
	 * @param string $endpoint The endpoint being accessed.
	 */
	private function set_jwt_auth( string $token, string $endpoint ): void {
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$_SERVER['REQUEST_URI'] = '/wp-json' . $endpoint;
		
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
	 * @param string $endpoint The endpoint being accessed.
	 */
	private function set_application_password_auth( string $username, string $password, string $endpoint ): void {
		$_SERVER['PHP_AUTH_USER'] = $username;
		$_SERVER['PHP_AUTH_PW'] = $password;
		$_SERVER['REQUEST_URI'] = '/wp-json' . $endpoint;
	}

	/**
	 * Create an application password for a user.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $name The application password name.
	 * @return array Array with 'password' and 'uuid' keys.
	 */
	private function create_application_password( int $user_id, string $name = 'Test App' ): array {
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
	 * Create STDIO format request.
	 *
	 * @param string $method The MCP method.
	 * @param array  $params The parameters.
	 * @return WP_REST_Request
	 */
	private function create_stdio_request( string $method, array $params = array() ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$request->set_body( wp_json_encode( array(
			'method' => $method,
			'params' => $params,
		) ) );
		$request->add_header( 'Content-Type', 'application/json' );
		return $request;
	}

	/**
	 * Create Streamable format request.
	 *
	 * @param string $method The MCP method.
	 * @param array  $params The parameters.
	 * @return WP_REST_Request
	 */
	private function create_streamable_request( string $method, array $params = array() ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp/streamable' );
		$request->set_body( wp_json_encode( array(
			'jsonrpc' => '2.0',
			'id'      => 1,
			'method'  => $method,
			'params'  => $params,
		) ) );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'Accept', 'application/json, text/event-stream' );
		return $request;
	}

	/**
	 * Test that both transports support JWT authentication.
	 */
	public function test_both_transports_support_jwt_authentication(): void {
		// Test STDIO with JWT
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp' );
		$stdio_request = $this->create_stdio_request( 'ping' );
		$stdio_response = rest_do_request( $stdio_request );
		$this->assertEquals( 200, $stdio_response->get_status(), 'STDIO should support JWT authentication' );

		// Test Streamable with JWT
		$this->clean_server_globals();
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp/streamable' );
		$streamable_request = $this->create_streamable_request( 'ping' );
		$streamable_response = rest_do_request( $streamable_request );
		$this->assertEquals( 200, $streamable_response->get_status(), 'Streamable should support JWT authentication' );
	}

	/**
	 * Test authentication method differences between transports.
	 */
	public function test_authentication_method_differences(): void {
		$app_password = $this->create_application_password( $this->admin_user->ID );

		// Test STDIO accepts application password
		$this->set_application_password_auth( $this->admin_user->user_login, $app_password['password'], '/wp/v2/wpmcp' );
		$stdio_request = $this->create_stdio_request( 'ping' );
		$stdio_response = rest_do_request( $stdio_request );
		$this->assertEquals( 200, $stdio_response->get_status(), 'STDIO should accept application password' );

		// Test Streamable rejects application password
		$this->clean_server_globals();
		$this->set_application_password_auth( $this->admin_user->user_login, $app_password['password'], '/wp/v2/wpmcp/streamable' );
		$streamable_request = $this->create_streamable_request( 'ping' );
		$streamable_response = rest_do_request( $streamable_request );
		$this->assertTrue(
			in_array( $streamable_response->get_status(), array( 401, 403 ), true ),
			'Streamable should reject application password'
		);
	}

	/**
	 * Test user capability differences between transports.
	 */
	public function test_user_capability_differences(): void {
		// Test STDIO accepts editor with JWT (uses is_user_logged_in())
		$this->set_jwt_auth( $this->editor_jwt_token, '/wp/v2/wpmcp' );
		$stdio_request = $this->create_stdio_request( 'ping' );
		$stdio_response = rest_do_request( $stdio_request );
		$this->assertEquals( 200, $stdio_response->get_status(), 'STDIO should accept editor role' );

		// Test Streamable rejects editor with JWT (requires manage_options)
		$this->clean_server_globals();
		$this->set_jwt_auth( $this->editor_jwt_token, '/wp/v2/wpmcp/streamable' );
		$streamable_request = $this->create_streamable_request( 'ping' );
		$streamable_response = rest_do_request( $streamable_request );
		$this->assertTrue(
			in_array( $streamable_response->get_status(), array( 401, 403 ), true ),
			'Streamable should reject editor role'
		);

		// Test both accept admin
		$this->clean_server_globals();
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp' );
		$stdio_request = $this->create_stdio_request( 'ping' );
		$stdio_response = rest_do_request( $stdio_request );
		$this->assertEquals( 200, $stdio_response->get_status(), 'STDIO should accept admin role' );

		$this->clean_server_globals();
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp/streamable' );
		$streamable_request = $this->create_streamable_request( 'ping' );
		$streamable_response = rest_do_request( $streamable_request );
		$this->assertEquals( 200, $streamable_response->get_status(), 'Streamable should accept admin role' );
	}

	/**
	 * Test response format differences between transports.
	 */
	public function test_response_format_differences(): void {
		// Test successful response formats
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp' );
		$stdio_request = $this->create_stdio_request( 'ping' );
		$stdio_response = rest_do_request( $stdio_request );
		$stdio_data = $stdio_response->get_data();

		$this->clean_server_globals();
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp/streamable' );
		$streamable_request = $this->create_streamable_request( 'ping' );
		$streamable_response = rest_do_request( $streamable_request );
		$streamable_data = $streamable_response->get_data();

		// STDIO should return WordPress format (direct result)
		$this->assertIsArray( $stdio_data, 'STDIO should return array directly' );
		$this->assertArrayNotHasKey( 'jsonrpc', $stdio_data, 'STDIO should not have jsonrpc field' );

		// Streamable should return JSON-RPC 2.0 format
		$this->assertIsArray( $streamable_data, 'Streamable should return array' );
		$this->assertArrayHasKey( 'jsonrpc', $streamable_data, 'Streamable should have jsonrpc field' );
		$this->assertEquals( '2.0', $streamable_data['jsonrpc'], 'Streamable should use JSON-RPC 2.0' );
		$this->assertArrayHasKey( 'id', $streamable_data, 'Streamable should have id field' );
		$this->assertArrayHasKey( 'result', $streamable_data, 'Streamable should have result field' );
	}

	/**
	 * Test error response format differences between transports.
	 */
	public function test_error_response_format_differences(): void {
		// Test error response formats
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp' );
		$stdio_request = $this->create_stdio_request( 'nonexistent_method' );
		$stdio_response = rest_do_request( $stdio_request );

		$this->clean_server_globals();
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp/streamable' );
		$streamable_request = $this->create_streamable_request( 'nonexistent_method' );
		$streamable_response = rest_do_request( $streamable_request );

		// STDIO should return error format (WordPress REST API converts WP_Error to WP_REST_Response)
		if ( $stdio_response instanceof \WP_Error ) {
			$this->assertInstanceOf( 'WP_Error', $stdio_response, 'STDIO should return WP_Error for errors' );
			$this->assertEquals( 'invalid_method', $stdio_response->get_error_code(), 'STDIO error should have correct code' );
		} else {
			$this->assertInstanceOf( 'WP_REST_Response', $stdio_response, 'STDIO should return WP_REST_Response for errors' );
			$this->assertTrue( $stdio_response->get_status() >= 400, 'STDIO should return error status' );
		}

		// Streamable should return WP_REST_Response with JSON-RPC error format
		$this->assertInstanceOf( 'WP_REST_Response', $streamable_response, 'Streamable should return WP_REST_Response for errors' );
		$streamable_data = $streamable_response->get_data();
		$this->assertArrayHasKey( 'jsonrpc', $streamable_data, 'Streamable error should have jsonrpc field' );
		$this->assertArrayHasKey( 'error', $streamable_data, 'Streamable error should have error field' );
		$this->assertArrayHasKey( 'id', $streamable_data, 'Streamable error should have id field' );
	}

	/**
	 * Test that both transports call the same underlying MCP methods.
	 */
	public function test_same_underlying_mcp_methods(): void {
		$test_methods = array( 'ping', 'tools/list', 'resources/list' );

		foreach ( $test_methods as $method ) {
			// Test STDIO
			$this->clean_server_globals();
			$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp' );
			$stdio_request = $this->create_stdio_request( $method );
			$stdio_response = rest_do_request( $stdio_request );

			// Test Streamable
			$this->clean_server_globals();
			$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp/streamable' );
			$streamable_request = $this->create_streamable_request( $method );
			$streamable_response = rest_do_request( $streamable_request );

			$this->assertEquals( 200, $stdio_response->get_status(), "STDIO should handle method: {$method}" );
			$this->assertEquals( 200, $streamable_response->get_status(), "Streamable should handle method: {$method}" );

			// Extract the actual result data for comparison
			$stdio_data = $stdio_response->get_data();
			$streamable_data = $streamable_response->get_data()['result'] ?? array();

			// Both should contain similar data structure (allowing for transport differences)
			$this->assertIsArray( $stdio_data, "STDIO {$method} should return array" );
			$this->assertIsArray( $streamable_data, "Streamable {$method} should return array" );
		}
	}

	/**
	 * Test request header requirements differences.
	 */
	public function test_request_header_requirements(): void {
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp' );

		// STDIO should work without special headers
		$stdio_request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$stdio_request->set_body( wp_json_encode( array( 'method' => 'ping' ) ) );
		$stdio_request->add_header( 'Content-Type', 'application/json' );
		$stdio_response = rest_do_request( $stdio_request );
		$this->assertEquals( 200, $stdio_response->get_status(), 'STDIO should work with basic headers' );

		// Streamable requires specific Accept header
		$this->clean_server_globals();
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp/streamable' );
		$streamable_request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp/streamable' );
		$streamable_request->set_body( wp_json_encode( array(
			'jsonrpc' => '2.0',
			'id'      => 1,
			'method'  => 'ping',
		) ) );
		$streamable_request->add_header( 'Content-Type', 'application/json' );
		// Missing Accept header
		$streamable_response = rest_do_request( $streamable_request );
		$this->assertEquals( 400, $streamable_response->get_status(), 'Streamable should require Accept header' );

		// Streamable with correct headers
		$streamable_request->add_header( 'Accept', 'application/json, text/event-stream' );
		$streamable_response = rest_do_request( $streamable_request );
		$this->assertEquals( 200, $streamable_response->get_status(), 'Streamable should work with correct headers' );
	}

	/**
	 * Test backward compatibility differences.
	 */
	public function test_backward_compatibility_differences(): void {
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp' );

		// STDIO supports old format where params are mixed with method
		$stdio_request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$stdio_request->set_body( wp_json_encode( array(
			'method'    => 'ping',
			'some_param' => 'some_value', // This should be treated as params
		) ) );
		$stdio_request->add_header( 'Content-Type', 'application/json' );
		$stdio_response = rest_do_request( $stdio_request );
		$this->assertEquals( 200, $stdio_response->get_status(), 'STDIO should support old request format' );

		// Streamable requires strict JSON-RPC 2.0 format
		$this->clean_server_globals();
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp/streamable' );
		$streamable_request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp/streamable' );
		$streamable_request->set_body( wp_json_encode( array(
			'method'    => 'ping',
			'some_param' => 'some_value', // Missing jsonrpc and id
		) ) );
		$streamable_request->add_header( 'Content-Type', 'application/json' );
		$streamable_request->add_header( 'Accept', 'application/json, text/event-stream' );
		$streamable_response = rest_do_request( $streamable_request );
		$this->assertEquals( 400, $streamable_response->get_status(), 'Streamable should require strict JSON-RPC format' );
	}

	/**
	 * Test MCP disabled behavior is consistent across transports.
	 */
	public function test_mcp_disabled_behavior_consistent(): void {
		// Disable MCP
		update_option( 'wordpress_mcp_settings', array( 'enabled' => false ) );

		// Test STDIO
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp' );
		$stdio_request = $this->create_stdio_request( 'ping' );
		$stdio_response = rest_do_request( $stdio_request );
		$this->assertEquals( 403, $stdio_response->get_status(), 'STDIO should return 403 when MCP disabled' );

		// Test Streamable
		$this->clean_server_globals();
		$this->set_jwt_auth( $this->admin_jwt_token, '/wp/v2/wpmcp/streamable' );
		$streamable_request = $this->create_streamable_request( 'ping' );
		$streamable_response = rest_do_request( $streamable_request );
		$this->assertEquals( 403, $streamable_response->get_status(), 'Streamable should return 403 when MCP disabled' );
	}
} 
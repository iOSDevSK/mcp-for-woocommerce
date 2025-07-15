<?php
/**
 * Tests for the JwtAuth class.
 *
 * @package WordPressMcp
 * @subpackage Tests
 */

namespace Automattic\WordpressMcp\Tests;

use Automattic\WordpressMcp\Auth\JwtAuth;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Test cases for the JwtAuth class.
 */
class JwtAuthTest extends WP_UnitTestCase {

	/**
	 * The JwtAuth instance.
	 *
	 * @var JwtAuth
	 */
	private JwtAuth $jwt_auth;

	/**
	 * The admin user.
	 *
	 * @var WP_User
	 */
	private WP_User $admin_user;

	/**
	 * The subscriber user.
	 *
	 * @var WP_User
	 */
	private WP_User $subscriber_user;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test users.
		$this->admin_user = $this->factory->user->create_and_get(
			array(
				'role'      => 'administrator',
				'user_pass' => 'test_password',
			)
		);

		$this->subscriber_user = $this->factory->user->create_and_get(
			array(
				'role'      => 'subscriber',
				'user_pass' => 'test_password',
			)
		);

		// Initialize JWT auth.
		$this->jwt_auth = new JwtAuth();

		// Initialize REST API.
		do_action( 'rest_api_init' );

		// Clear any existing tokens.
		delete_option( 'jwt_token_registry' );
		delete_option( 'wpmcp_jwt_secret_key' );
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		// Clear options.
		delete_option( 'jwt_token_registry' );
		delete_option( 'wpmcp_jwt_secret_key' );
		
		parent::tearDown();
	}

	/**
	 * Test JWT routes are registered.
	 */
	public function test_jwt_routes_are_registered(): void {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/jwt-auth/v1/token', $routes );
		$this->assertArrayHasKey( '/jwt-auth/v1/revoke', $routes );
		$this->assertArrayHasKey( '/jwt-auth/v1/tokens', $routes );
	}

	/**
	 * Test token generation with valid credentials.
	 */
	public function test_generate_token_with_valid_credentials(): void {
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body(
			wp_json_encode(
				array(
					'username' => $this->admin_user->user_login,
					'password' => 'test_password',
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'token', $data );
		$this->assertArrayHasKey( 'user_id', $data );
		$this->assertArrayHasKey( 'expires_in', $data );
		$this->assertArrayHasKey( 'expires_at', $data );
		$this->assertEquals( $this->admin_user->ID, $data['user_id'] );
		$this->assertEquals( 3600, $data['expires_in'] );
		$this->assertIsString( $data['token'] );
	}

	/**
	 * Test token generation with invalid credentials.
	 */
	public function test_generate_token_with_invalid_credentials(): void {
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body(
			wp_json_encode(
				array(
					'username' => 'invalid_user',
					'password' => 'invalid_password',
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'invalid_credentials', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid username or password', $response->get_data()['message'] );
	}

	/**
	 * Test token generation with custom expiration time.
	 */
	public function test_generate_token_with_custom_expiration(): void {
		$expires_in = 7200; // 2 hours

		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body(
			wp_json_encode(
				array(
					'username'   => $this->admin_user->user_login,
					'password'   => 'test_password',
					'expires_in' => $expires_in,
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expires_in, $data['expires_in'] );
	}

	/**
	 * Test token generation with invalid expiration time.
	 */
	public function test_generate_token_with_invalid_expiration(): void {
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body(
			wp_json_encode(
				array(
					'username'   => $this->admin_user->user_login,
					'password'   => 'test_password',
					'expires_in' => 1800, // 30 minutes - too short
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		// WordPress REST API validates parameters before our code runs
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Test token generation for already logged in user.
	 */
	public function test_generate_token_for_logged_in_user(): void {
		wp_set_current_user( $this->admin_user->ID );

		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->admin_user->ID, $data['user_id'] );
	}

	/**
	 * Test token validation.
	 */
	public function test_token_validation(): void {
		// Generate a token first.
		wp_set_current_user( $this->admin_user->ID );
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$token    = $response->get_data()['token'];

		// Test authentication with the token.
		$_SERVER['REQUEST_URI']        = '/wp-json/wp/v2/wpmcp';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertTrue( $auth_result );
		$this->assertEquals( $this->admin_user->ID, get_current_user_id() );
	}

	/**
	 * Test authentication with invalid token.
	 */
	public function test_authentication_with_invalid_token(): void {
		$_SERVER['REQUEST_URI']        = '/wp-json/wp/v2/wpmcp';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid_token';

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'invalid_token', $auth_result->get_error_code() );
	}

	/**
	 * Test authentication with missing authorization header (not logged in).
	 */
	public function test_authentication_with_missing_header_not_logged_in(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/wpmcp';
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		wp_set_current_user( 0 ); // Ensure no user is logged in

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'unauthorized', $auth_result->get_error_code() );
		$this->assertEquals( 'Authentication required. Please provide a Bearer token or log in as an administrator.', $auth_result->get_error_message() );
	}

	/**
	 * Test authentication with missing authorization header but logged in user.
	 */
	public function test_authentication_with_missing_header_logged_in(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/wpmcp';
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		wp_set_current_user( $this->admin_user->ID ); // Log in as admin

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertTrue( $auth_result );
	}

	/**
	 * Test authentication doesn't apply to non-MCP endpoints.
	 */
	public function test_authentication_not_applied_to_non_mcp_endpoints(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/posts';
		unset( $_SERVER['HTTP_AUTHORIZATION'] );

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertNull( $auth_result );
	}

	/**
	 * Test token revocation.
	 */
	public function test_token_revocation(): void {
		// Generate a token first.
		wp_set_current_user( $this->admin_user->ID );
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$token    = $response->get_data()['token'];

		// Decode token to get JTI using the actual secret key.
		$secret_key = get_option( 'wpmcp_jwt_secret_key' );
		$decoded    = JWT::decode( $token, new Key( $secret_key, 'HS256' ) );
		$jti        = $decoded->jti ?? '';

		// Revoke the token.
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/revoke' );
		$request->set_body(
			wp_json_encode(
				array(
					'jti' => $jti,
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Token revoked successfully.', $response->get_data()['message'] );
	}

	/**
	 * Test token revocation with missing JTI.
	 */
	public function test_token_revocation_with_missing_jti(): void {
		wp_set_current_user( $this->admin_user->ID );

		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/revoke' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'missing_jti', $response->get_data()['code'] );
	}

	/**
	 * Test token revocation with non-existent JTI.
	 */
	public function test_token_revocation_with_non_existent_jti(): void {
		wp_set_current_user( $this->admin_user->ID );

		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/revoke' );
		$request->set_body(
			wp_json_encode(
				array(
					'jti' => 'non_existent_jti',
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'token_not_found', $response->get_data()['code'] );
	}

	/**
	 * Test listing tokens.
	 */
	public function test_list_tokens(): void {
		wp_set_current_user( $this->admin_user->ID );

		// Generate a token first.
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );
		rest_do_request( $request );

		// List tokens.
		$request  = new WP_REST_Request( 'GET', '/jwt-auth/v1/tokens' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertCount( 1, $data );
		$this->assertArrayHasKey( 'jti', $data[0] );
		$this->assertArrayHasKey( 'user', $data[0] );
		$this->assertArrayHasKey( 'issued_at', $data[0] );
		$this->assertArrayHasKey( 'expires_at', $data[0] );
		$this->assertArrayHasKey( 'revoked', $data[0] );
		$this->assertArrayHasKey( 'is_expired', $data[0] );
		$this->assertEquals( $this->admin_user->ID, $data[0]['user']['id'] );
		$this->assertFalse( $data[0]['revoked'] );
		$this->assertFalse( $data[0]['is_expired'] );
	}

	/**
	 * Test permission check for token management.
	 */
	public function test_permission_check_for_token_management(): void {
		wp_set_current_user( $this->subscriber_user->ID );

		// Try to list tokens as subscriber.
		$request  = new WP_REST_Request( 'GET', '/jwt-auth/v1/tokens' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'rest_forbidden', $response->get_data()['code'] );

		// Try to revoke tokens as subscriber.
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/revoke' );
		$request->set_body(
			wp_json_encode(
				array(
					'jti' => 'some_jti',
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test authentication with revoked token.
	 */
	public function test_authentication_with_revoked_token(): void {
		// Generate a token.
		wp_set_current_user( $this->admin_user->ID );
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$token    = $response->get_data()['token'];

		// Manually revoke the token in registry.
		$registry = get_option( 'jwt_token_registry', array() );
		foreach ( $registry as $jti => &$token_data ) {
			$token_data['revoked'] = true;
		}
		update_option( 'jwt_token_registry', $registry );

		// Try to authenticate with revoked token.
		$_SERVER['REQUEST_URI']        = '/wp-json/wp/v2/wpmcp';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'token_invalid', $auth_result->get_error_code() );
	}

	/**
	 * Test list tokens removes expired tokens.
	 */
	public function test_list_tokens_removes_expired_tokens(): void {
		wp_set_current_user( $this->admin_user->ID );

		// Manually add an expired token to registry.
		$jti      = wp_generate_password( 32, false );
		$registry = array(
			$jti => array(
				'user_id'    => $this->admin_user->ID,
				'issued_at'  => time() - 7200, // 2 hours ago
				'expires_at' => time() - 3600, // 1 hour ago (expired)
				'revoked'    => false,
			),
		);
		update_option( 'jwt_token_registry', $registry );

		// List tokens.
		$request  = new WP_REST_Request( 'GET', '/jwt-auth/v1/tokens' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertCount( 0, $data ); // Expired token should be removed

		// Verify the expired token was removed from the registry.
		$updated_registry = get_option( 'jwt_token_registry', array() );
		$this->assertArrayNotHasKey( $jti, $updated_registry );
	}

	/**
	 * Test JWT secret key generation.
	 */
	public function test_jwt_secret_key_generation(): void {
		// Delete any existing key.
		delete_option( 'wpmcp_jwt_secret_key' );

		// Generate a token which should trigger secret key generation.
		wp_set_current_user( $this->admin_user->ID );
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );
		rest_do_request( $request );

		$secret_key = get_option( 'wpmcp_jwt_secret_key' );

		$this->assertNotEmpty( $secret_key );
		$this->assertEquals( 64, strlen( $secret_key ) );
	}

	/**
	 * Test authentication with non-existent user.
	 */
	public function test_authentication_with_non_existent_user(): void {
		// Generate a token with a valid user.
		wp_set_current_user( $this->admin_user->ID );
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$token    = $response->get_data()['token'];

		// Delete the user.
		wp_delete_user( $this->admin_user->ID );

		// Try to authenticate with the token.
		$_SERVER['REQUEST_URI']        = '/wp-json/wp/v2/wpmcp';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'invalid_token', $auth_result->get_error_code() );
		$this->assertEquals( 'User associated with token no longer exists.', $auth_result->get_error_message() );
	}

	/**
	 * Test authentication with malformed JWT token.
	 */
	public function test_authentication_with_malformed_token(): void {
		$_SERVER['REQUEST_URI']        = '/wp-json/wp/v2/wpmcp';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer malformed.jwt.token';

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'invalid_token', $auth_result->get_error_code() );
	}

	/**
	 * Test authentication with expired token.
	 */
	public function test_authentication_with_expired_token(): void {
		// Generate a token with short expiration.
		wp_set_current_user( $this->admin_user->ID );
		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body(
			wp_json_encode(
				array(
					'expires_in' => 3600, // 1 hour
				)
			)
		);
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		$token    = $response->get_data()['token'];

		// Manually expire the token by updating the registry.
		$registry = get_option( 'jwt_token_registry', array() );
		foreach ( $registry as $jti => &$token_data ) {
			$token_data['expires_at'] = time() - 1; // Expired 1 second ago
		}
		update_option( 'jwt_token_registry', $registry );

		// Try to authenticate with expired token.
		$_SERVER['REQUEST_URI']        = '/wp-json/wp/v2/wpmcp';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

		$auth_result = $this->jwt_auth->authenticate_request( null );

		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'token_invalid', $auth_result->get_error_code() );
	}

	/**
	 * Test token generation without credentials for non-logged-in user.
	 */
	public function test_generate_token_without_credentials_not_logged_in(): void {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/jwt-auth/v1/token' );
		$request->set_body( wp_json_encode( array() ) );
		$request->add_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'invalid_credentials', $response->get_data()['code'] );
	}

	/**
	 * Test authorization header with different formats.
	 */
	public function test_authentication_with_different_auth_header_formats(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/wpmcp';

		// Test with "bearer" (lowercase)
		$_SERVER['HTTP_AUTHORIZATION'] = 'bearer invalid_token';
		$auth_result                   = $this->jwt_auth->authenticate_request( null );
		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'unauthorized', $auth_result->get_error_code() );

		// Test with just the token (no Bearer prefix)
		$_SERVER['HTTP_AUTHORIZATION'] = 'invalid_token';
		$auth_result                   = $this->jwt_auth->authenticate_request( null );
		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'unauthorized', $auth_result->get_error_code() );

		// Test with empty authorization header
		$_SERVER['HTTP_AUTHORIZATION'] = '';
		$auth_result                   = $this->jwt_auth->authenticate_request( null );
		$this->assertInstanceOf( 'WP_Error', $auth_result );
		$this->assertEquals( 'unauthorized', $auth_result->get_error_code() );
	}
} 
<?php
/**
 * Tests for the McpStreamableTransport class.
 *
 * @package WordPressMcp
 * @subpackage Tests
 */

namespace Automattic\WordpressMcp\Tests;

use Automattic\WordpressMcp\Core\McpStreamableTransport;
use WP_REST_Request;

/**
 * Test cases for the McpStreamableTransport class.
 * Tests Streamable-specific functionality including JSON-RPC 2.0 format,
 * header validation, batch requests, and JWT-only authentication.
 */
class McpStreamableTransportTest extends McpTransportTestBase {

	/**
	 * The Streamable transport instance.
	 *
	 * @var McpStreamableTransport
	 */
	private McpStreamableTransport $streamable_transport;

	/**
	 * Set up the Streamable transport test.
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Initialize Streamable transport
		$this->streamable_transport = new McpStreamableTransport( $this->wp_mcp );
	}

	/**
	 * Get the transport endpoint for Streamable.
	 *
	 * @return string
	 */
	protected function get_transport_endpoint(): string {
		return '/wp/v2/wpmcp/streamable';
	}

	/**
	 * Create a transport request for Streamable.
	 *
	 * @param string $method The MCP method to call.
	 * @param array  $params The parameters for the method.
	 * @param array  $headers Additional headers to include.
	 * @return WP_REST_Request
	 */
	protected function create_transport_request( string $method, array $params = array(), array $headers = array() ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', $this->get_transport_endpoint() );
		
		// JSON-RPC 2.0 format with required fields
		$body = array(
			'jsonrpc' => '2.0',
			'id'      => 1,
			'method'  => $method,
			'params'  => $params,
		);

		$request->set_body( wp_json_encode( $body ) );
		
		// Streamable requires specific headers
		$default_headers = array(
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json, text/event-stream',
		);
		
		$all_headers = array_merge( $default_headers, $headers );
		
		foreach ( $all_headers as $key => $value ) {
			$request->add_header( $key, $value );
		}

		return $request;
	}

	/**
	 * Assert a valid response for Streamable transport.
	 *
	 * @param mixed $response The response to validate.
	 * @param array $expected_data Expected data in the response.
	 */
	protected function assert_valid_response( $response, array $expected_data = array() ): void {
		$this->assertEquals( 200, $response->get_status(), 'Response should have 200 status' );
		$this->assertInstanceOf( 'WP_REST_Response', $response, 'Should return WP_REST_Response' );
		
		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response data should be an array' );
		
		// Validate JSON-RPC 2.0 format
		$this->assertArrayHasKey( 'jsonrpc', $data, 'Response should contain jsonrpc field' );
		$this->assertEquals( '2.0', $data['jsonrpc'], 'Should use JSON-RPC 2.0' );
		$this->assertArrayHasKey( 'id', $data, 'Response should contain id field' );
		$this->assertArrayHasKey( 'result', $data, 'Response should contain result field' );
		
		// Check for expected data if provided
		if ( ! empty( $expected_data ) ) {
			$result = $data['result'] ?? array();
			foreach ( $expected_data as $key => $expected_value ) {
				$this->assertArrayHasKey( $key, $result, "Result should contain key: {$key}" );
				if ( $expected_value !== null ) {
					$this->assertEquals( $expected_value, $result[ $key ], "Value for {$key} should match expected" );
				}
			}
		}
	}

	/**
	 * Assert an error response for Streamable transport.
	 *
	 * @param mixed  $response The response to validate.
	 * @param string $expected_error_code Expected error code.
	 * @param int    $expected_status Expected HTTP status code.
	 */
	protected function assert_error_response( $response, string $expected_error_code, int $expected_status ): void {
		$this->assertEquals( $expected_status, $response->get_status(), "Should return {$expected_status} status" );
		$this->assertInstanceOf( 'WP_REST_Response', $response, 'Should return WP_REST_Response even for errors' );
		
		$data = $response->get_data();
		$this->assertIsArray( $data, 'Error response data should be an array' );
		
		// For JSON-RPC errors, check the format
		if ( isset( $data['jsonrpc'] ) ) {
			$this->assertEquals( '2.0', $data['jsonrpc'], 'Error should use JSON-RPC 2.0' );
			$this->assertArrayHasKey( 'error', $data, 'Error response should contain error field' );
			$this->assertArrayHasKey( 'id', $data, 'Error response should contain id field' );
		}
	}

	/**
	 * Test Streamable authentication with JWT token.
	 */
	public function test_streamable_authentication_with_jwt(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );

		$this->assert_valid_response( $response );
	}

	/**
	 * Test Streamable rejects application password authentication.
	 * Streamable should only accept JWT authentication.
	 */
	public function test_streamable_rejects_application_password(): void {
		// Create application password for admin user
		$app_password = $this->create_application_password( $this->admin_user->ID );
		
		// Set application password authentication
		$this->set_application_password_auth( $this->admin_user->user_login, $app_password['password'] );

		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );

		// Should be rejected even though user has valid application password
		$this->assertTrue(
			in_array( $response->get_status(), array( 401, 403 ), true ),
			'Streamable should reject application password authentication'
		);
	}

	/**
	 * Test Streamable requires manage_options capability.
	 * Editor users should not have access even with valid JWT.
	 */
	public function test_streamable_requires_manage_options_capability(): void {
		// Test with editor JWT (should fail)
		$this->set_jwt_auth( $this->editor_jwt_token );

		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );

		$this->assertTrue(
			in_array( $response->get_status(), array( 401, 403 ), true ),
			'Editor should not have access to Streamable transport'
		);

		// Test with admin JWT (should succeed)
		$this->set_jwt_auth( $this->admin_jwt_token );

		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );

		$this->assert_valid_response( $response );
	}

	/**
	 * Test Streamable Accept header validation.
	 */
	public function test_streamable_accept_header_validation(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		// Test with missing Accept header
		$request = $this->create_transport_request( 'ping', array(), array( 'Accept' => '' ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status(), 'Missing Accept header should return 400' );

		// Test with incomplete Accept header (missing text/event-stream)
		$request = $this->create_transport_request( 'ping', array(), array( 'Accept' => 'application/json' ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status(), 'Incomplete Accept header should return 400' );

		// Test with valid Accept header
		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );
		$this->assert_valid_response( $response );
	}

	/**
	 * Test Streamable Content-Type header validation.
	 */
	public function test_streamable_content_type_validation(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		// Test with invalid Content-Type
		$request = $this->create_transport_request( 'ping', array(), array( 'Content-Type' => 'text/plain' ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status(), 'Invalid Content-Type should return 400' );

		// Test with valid Content-Type
		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );
		$this->assert_valid_response( $response );
	}

	/**
	 * Test Streamable OPTIONS preflight handling.
	 */
	public function test_streamable_options_preflight(): void {
		// OPTIONS requests typically don't require authentication for CORS preflight
		$request = new WP_REST_Request( 'OPTIONS', $this->get_transport_endpoint() );
		$response = rest_do_request( $request );

		// Check that OPTIONS is handled (should be 204 or 200)
		$this->assertTrue(
			in_array( $response->get_status(), array( 200, 204 ), true ),
			'OPTIONS should return 200 or 204, got: ' . $response->get_status()
		);
	}

	/**
	 * Test Streamable JSON-RPC 2.0 format validation.
	 */
	public function test_streamable_jsonrpc_format_validation(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		// Test missing jsonrpc field
		$request = new WP_REST_Request( 'POST', $this->get_transport_endpoint() );
		$request->set_body( wp_json_encode( array(
			'id'     => 1,
			'method' => 'ping',
		) ) );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'Accept', 'application/json, text/event-stream' );

		$response = rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status(), 'Missing jsonrpc field should return 400' );

		// Test wrong jsonrpc version
		$request = new WP_REST_Request( 'POST', $this->get_transport_endpoint() );
		$request->set_body( wp_json_encode( array(
			'jsonrpc' => '1.0',
			'id'      => 1,
			'method'  => 'ping',
		) ) );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'Accept', 'application/json, text/event-stream' );

		$response = rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status(), 'Wrong jsonrpc version should return 400' );

		// Test missing method field (this should be an error)
		$request = new WP_REST_Request( 'POST', $this->get_transport_endpoint() );
		$request->set_body( wp_json_encode( array(
			'jsonrpc' => '2.0',
			'id'      => 1,
		) ) );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'Accept', 'application/json, text/event-stream' );

		$response = rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status(), 'Missing method field should return 400' );
	}

	/**
	 * Test Streamable batch request handling.
	 */
	public function test_streamable_batch_requests(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		// Test batch of requests
		$batch = array(
			array(
				'jsonrpc' => '2.0',
				'id'      => 1,
				'method'  => 'ping',
				'params'  => array(),
			),
			array(
				'jsonrpc' => '2.0',
				'id'      => 2,
				'method'  => 'tools/list',
				'params'  => array(),
			),
		);

		$request = new WP_REST_Request( 'POST', $this->get_transport_endpoint() );
		$request->set_body( wp_json_encode( $batch ) );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'Accept', 'application/json, text/event-stream' );

		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status(), 'Batch request should succeed' );
		
		$data = $response->get_data();
		$this->assertIsArray( $data, 'Batch response should be an array' );
		$this->assertCount( 2, $data, 'Batch response should contain 2 items' );
		
		// Check each response in the batch
		foreach ( $data as $item ) {
			$this->assertArrayHasKey( 'jsonrpc', $item, 'Each batch item should have jsonrpc' );
			$this->assertEquals( '2.0', $item['jsonrpc'], 'Each batch item should use JSON-RPC 2.0' );
			$this->assertArrayHasKey( 'id', $item, 'Each batch item should have id' );
		}
	}

	/**
	 * Test Streamable notification handling (requests without id).
	 */
	public function test_streamable_notification_handling(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		// Test notification (no id field)
		$request = new WP_REST_Request( 'POST', $this->get_transport_endpoint() );
		$request->set_body( wp_json_encode( array(
			'jsonrpc' => '2.0',
			'method'  => 'ping',
			'params'  => array(),
		) ) );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'Accept', 'application/json, text/event-stream' );

		$response = rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status(), 'Notification should return 202 Accepted' );
		$this->assertNull( $response->get_data(), 'Notification should return no body' );
	}

	/**
	 * Test Streamable mixed batch with requests and notifications.
	 */
	public function test_streamable_mixed_batch(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		// Test batch with both request and notification
		$batch = array(
			array(
				'jsonrpc' => '2.0',
				'id'      => 1,
				'method'  => 'ping',
				'params'  => array(),
			),
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/list', // notification (no id)
				'params'  => array(),
			),
		);

		$request = new WP_REST_Request( 'POST', $this->get_transport_endpoint() );
		$request->set_body( wp_json_encode( $batch ) );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'Accept', 'application/json, text/event-stream' );

		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status(), 'Mixed batch should succeed' );
		
		$data = $response->get_data();
		
		$this->assertIsArray( $data, 'Mixed batch response should be an array' );
		
		// Check if this is a single response (associative array with jsonrpc structure) 
		// or a batch response (indexed array of responses)
		$is_batch_response = isset( $data[0] ) && is_array( $data[0] );
		
		if ( $is_batch_response ) {
			// Batch response - should have 1 item (only the request, not the notification)
			$this->assertCount( 1, $data, 'Mixed batch should only return responses for requests (not notifications). Got: ' . count( $data ) . ' responses.' );
			$response_item = $data[0];
		} else {
			// Single response - this is the expected case for our mixed batch
			$this->assertArrayHasKey( 'jsonrpc', $data, 'Single response should have jsonrpc field' );
			$this->assertArrayHasKey( 'id', $data, 'Single response should have id field' );
			$response_item = $data;
		}
		
		// Should only contain the response to the request (id=1)
		$this->assertEquals( 1, $response_item['id'], 'Response should be for request with id=1' );
	}

	/**
	 * Test Streamable HTTP method restrictions.
	 */
	public function test_streamable_http_method_restrictions(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		// Test allowed methods
		$allowed_methods = array( 'POST', 'OPTIONS' );
		foreach ( $allowed_methods as $method ) {
			$request = new WP_REST_Request( $method, $this->get_transport_endpoint() );
			if ( 'POST' === $method ) {
				$request->set_body( wp_json_encode( array(
					'jsonrpc' => '2.0',
					'id'      => 1,
					'method'  => 'ping',
				) ) );
				$request->add_header( 'Content-Type', 'application/json' );
				$request->add_header( 'Accept', 'application/json, text/event-stream' );
			}
			
			$response = rest_do_request( $request );
			$this->assertTrue(
				in_array( $response->get_status(), array( 200, 204 ), true ),
				"HTTP {$method} should be allowed"
			);
		}

		// Test disallowed methods
		$disallowed_methods = array( 'GET', 'PUT', 'DELETE', 'PATCH' );
		foreach ( $disallowed_methods as $method ) {
			$request = new WP_REST_Request( $method, $this->get_transport_endpoint() );
			$response = rest_do_request( $request );
			
			$this->assertEquals( 405, $response->get_status(), "HTTP {$method} should return 405 Method Not Allowed" );
		}
	}

	/**
	 * Test Streamable initialize method handling.
	 */
	public function test_streamable_initialize_method(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		$request = $this->create_transport_request( 'initialize', array(
			'protocolVersion' => '2024-11-05',
			'capabilities'    => array(),
		) );
		$response = rest_do_request( $request );

		$this->assert_valid_response( $response );
		
		$data = $response->get_data();
		$result = $data['result'] ?? array();
		$this->assertArrayHasKey( 'protocolVersion', $result, 'Initialize should return protocolVersion' );
		$this->assertArrayHasKey( 'capabilities', $result, 'Initialize should return capabilities' );
	}

	/**
	 * Test Streamable error response format.
	 */
	public function test_streamable_error_response_format(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		$request = $this->create_transport_request( 'nonexistent_method' );
		$response = rest_do_request( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$data = $response->get_data();
		
		// Should be JSON-RPC 2.0 error format
		$this->assertArrayHasKey( 'jsonrpc', $data, 'Error should have jsonrpc field' );
		$this->assertEquals( '2.0', $data['jsonrpc'], 'Error should use JSON-RPC 2.0' );
		$this->assertArrayHasKey( 'id', $data, 'Error should have id field' );
		$this->assertArrayHasKey( 'error', $data, 'Error should have error field' );
		
		$error = $data['error'];
		$this->assertIsArray( $error, 'Error field should be an array' );
		$this->assertArrayHasKey( 'code', $error, 'Error should have code' );
		$this->assertArrayHasKey( 'message', $error, 'Error should have message' );
	}

	/**
	 * Test Streamable permission check method.
	 */
	public function test_streamable_permission_check(): void {
		// Test with admin user (should pass)
		$this->set_jwt_auth( $this->admin_jwt_token );
		$result = $this->streamable_transport->check_permission();
		$this->assertTrue( $result, 'Admin should pass permission check' );

		// Test without authentication
		$this->clear_auth();
		$result = $this->streamable_transport->check_permission();
		$this->assertFalse( $result, 'No authentication should fail permission check' );

		// Test with MCP disabled
		update_option( 'wordpress_mcp_settings', array( 'enabled' => false ) );
		$this->set_jwt_auth( $this->admin_jwt_token );
		$result = $this->streamable_transport->check_permission();
		$this->assertInstanceOf( 'WP_Error', $result, 'Disabled MCP should return WP_Error' );
		$this->assertEquals( 'mcp_disabled', $result->get_error_code() );
	}

	/**
	 * Test Streamable with malformed JSON.
	 */
	public function test_streamable_with_malformed_json(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		$request = new WP_REST_Request( 'POST', $this->get_transport_endpoint() );
		$request->set_body( '{"jsonrpc": "2.0", "id": 1,' ); // Malformed JSON
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'Accept', 'application/json, text/event-stream' );

		$response = rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status(), 'Malformed JSON should return 400' );
	}

	/**
	 * Test Streamable CORS headers.
	 */
	public function test_streamable_cors_headers(): void {
		$this->set_jwt_auth( $this->admin_jwt_token );

		$request = $this->create_transport_request( 'ping' );
		$response = rest_do_request( $request );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $headers, 'Should include CORS origin header' );
		$this->assertArrayHasKey( 'Access-Control-Allow-Methods', $headers, 'Should include CORS methods header' );
	}
} 
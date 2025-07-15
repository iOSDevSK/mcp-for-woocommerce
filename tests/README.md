# WordPress MCP Plugin Testing Framework

This directory contains the testing framework for the WordPress MCP plugin. It covers transport protocols (STDIO and Streamable), authentication methods, and MCP tool functionalities.

## Test Structure

The main tests are located in the `phpunit` directory.

### Base Test Class

-   **`McpTransportTestBase.php`** - Abstract base class providing common functionality for transport testing:
    -   JWT token generation and management
    -   Authentication helpers (JWT and Application Password)
    -   Common assertion methods
    -   Data providers for testing scenarios

### Transport-Specific Tests

-   **`McpStdioTransportTest.php`** - Tests for STDIO transport protocol:

    -   WordPress-style responses (`WP_REST_Response`/`WP_Error`)
    -   Both JWT and Application Password authentication
    -   Backward compatibility testing
    -   Permission validation using `is_user_logged_in()`

-   **`McpStreamableTransportTest.php`** - Tests for Streamable transport protocol:
    -   JSON-RPC 2.0 format validation
    -   JWT-only authentication (rejects Application Passwords)
    -   Header validation (Accept, Content-Type)
    -   Batch request handling
    -   Notification handling (requests without ID)
    -   Permission validation using `current_user_can('manage_options')`

### Integration Tests

-   **`McpTransportIntegrationTest.php`** - Comparative testing of both transports:
    -   Side-by-side authentication testing
    -   Response format comparisons
    -   Error handling differences
    -   Protocol requirement validation

### Authentication Tests (`JwtAuthTest.php`)

This test suite provides comprehensive coverage for all JWT authentication functionality.

#### Token Generation Tests

-   ✅ `test_generate_token_with_valid_credentials` - Valid username/password authentication
-   ✅ `test_generate_token_with_invalid_credentials` - Invalid credentials handling
-   ✅ `test_generate_token_with_custom_expiration` - Custom token expiration time
-   ✅ `test_generate_token_with_invalid_expiration` - Invalid expiration parameters
-   ✅ `test_generate_token_for_logged_in_user` - Token generation for authenticated users
-   ✅ `test_generate_token_without_credentials_not_logged_in` - Missing credentials error

#### Authentication Flow Tests

-   ✅ `test_token_validation` - Valid token authentication
-   ✅ `test_authentication_with_invalid_token` - Invalid token handling
-   ✅ `test_authentication_with_missing_header` - Missing Authorization header
-   ✅ `test_authentication_not_applied_to_non_mcp_endpoints` - Selective authentication
-   ✅ `test_authentication_with_revoked_token` - Revoked token handling
-   ✅ `test_authentication_with_non_existent_user` - Deleted user scenarios
-   ✅ `test_authentication_with_malformed_token` - Malformed JWT handling
-   ✅ `test_authentication_with_expired_token` - Token expiration
-   ✅ `test_authentication_with_different_auth_header_formats` - Header format validation

#### Token Management Tests

-   ✅ `test_token_revocation` - Token revocation functionality
-   ✅ `test_token_revocation_with_missing_jti` - Missing JTI parameter
-   ✅ `test_token_revocation_with_non_existent_jti` - Non-existent token handling
-   ✅ `test_list_tokens` - Token listing functionality
-   ✅ `test_list_tokens_removes_expired_tokens` - Automatic cleanup
-   ✅ `test_permission_check_for_token_management` - Permission validation

#### System Tests

-   ✅ `test_jwt_routes_are_registered` - REST API route registration
-   ✅ `test_jwt_secret_key_generation` - Automatic secret key generation

## Authentication Matrix

| Transport  | JWT | Application Password | User Role Required |
| ---------- | --- | -------------------- | ------------------ |
| STDIO      | ✅  | ✅                   | Any authenticated  |
| Streamable | ✅  | ❌                   | Administrator      |

## Key Differences Between Transports

### STDIO Transport (`/wp/v2/wpmcp`)

-   **Authentication**: `is_user_logged_in()` - accepts both JWT and Application Password
-   **Response Format**: WordPress-style (`WP_REST_Response` for success, `WP_Error` for errors)
-   **Request Format**: Simple `{method, params}` structure
-   **Headers**: Basic `Content-Type: application/json`
-   **Backward Compatibility**: Supports legacy request formats
-   **User Permissions**: Any authenticated user

### Streamable Transport (`/wp/v2/wpmcp/streamable`)

-   **Authentication**: JWT only with `current_user_can('manage_options')`
-   **Response Format**: JSON-RPC 2.0 (`{jsonrpc, id, result}` or `{jsonrpc, id, error}`)
-   **Request Format**: Strict JSON-RPC 2.0 `{jsonrpc: "2.0", id, method, params}`
-   **Headers**: Requires `Accept: application/json, text/event-stream`
-   **Batch Support**: Handles arrays of requests
-   **Notifications**: Supports requests without ID (returns 202)
-   **User Permissions**: Administrator only

## Running Tests

### Individual Test Classes

```bash
# Test STDIO transport
vendor/bin/phpunit wp-content/plugins/wordpress-mcp/tests/phpunit/McpStdioTransportTest.php

# Test Streamable transport
vendor/bin/phpunit wp-content/plugins/wordpress-mcp/tests/phpunit/McpStreamableTransportTest.php

# Test integration between transports
vendor/bin/phpunit wp-content/plugins/wordpress-mcp/tests/phpunit/McpTransportIntegrationTest.php

# Test JWT authentication
vendor/bin/phpunit wp-content/plugins/wordpress-mcp/tests/phpunit/JwtAuthTest.php
```

To run a specific test method in the JWT suite:

```bash
vendor/bin/phpunit tests/phpunit/JwtAuthTest.php --filter test_generate_token_with_valid_credentials
```

### Run Test Suites

```bash
# Run all Transport tests
vendor/bin/phpunit wp-content/plugins/wordpress-mcp/tests/phpunit/ --filter="Mcp.*Transport"

# Run all Authentication tests
vendor/bin/phpunit wp-content/plugins/wordpress-mcp/tests/phpunit/ --filter=".*Auth"
```

## Test Coverage

### Authentication Testing

-   ✅ JWT authentication for both transports
-   ✅ Application Password authentication (STDIO only)
-   ✅ Authentication rejection scenarios
-   ✅ User role and capability validation
-   ✅ Token validation and expiration
-   ✅ Authentication fallback behavior

### Protocol Testing

-   ✅ Request format validation
-   ✅ Response format validation
-   ✅ Header requirement validation
-   ✅ HTTP method restrictions
-   ✅ Error response formats
-   ✅ Batch request handling (Streamable)
-   ✅ Notification handling (Streamable)

### Integration Testing

-   ✅ Cross-transport comparison
-   ✅ Same MCP methods on both transports
-   ✅ Authentication method differences
-   ✅ Response format differences
-   ✅ Error handling consistency
-   ✅ MCP enable/disable behavior

### Edge Case Testing

-   ✅ Malformed JSON handling
-   ✅ Large request handling
-   ✅ Invalid authentication scenarios
-   ✅ Missing required headers
-   ✅ CORS header validation
-   ✅ OPTIONS preflight handling

## Extending the Tests

### Adding New Transport Tests

1. Extend `McpTransportTestBase`
2. Implement abstract methods:
    - `get_transport_endpoint()`
    - `create_transport_request()`
    - `assert_valid_response()`
    - `assert_error_response()`

### Adding New MCP Method Tests

Use the existing tool tests as templates:

-   `McpPostsToolsTest.php`
-   `McpMediaToolsTest.php`
-   `McpUsersToolsTest.php`
-   etc.

### Testing Custom Authentication

The base class provides helpers for:

-   JWT token generation
-   Application Password creation
-   Authentication state management
-   Server global cleanup

## Test Environment

The tests use WordPress's unit testing framework and require:

-   PHP 7.4+
-   WordPress test environment
-   PHPUnit 9.x
-   Firebase JWT library

All test data (e.g., users) is automatically cleaned up after each test run.

## Best Practices

1. **Always clean up authentication state** between tests using `clean_server_globals()`
2. **Use appropriate assertion methods** for each transport type
3. **Test both success and error scenarios** for all methods
4. **Validate response formats** specific to each transport
5. **Test authentication edge cases** like expired tokens and invalid users
6. **Use data providers** for testing multiple scenarios efficiently

## Troubleshooting

### Common Issues

-   **Linter Errors**: The static analysis tool shows false positives for WordPress/PHPUnit classes that exist at runtime.
-   **Authentication Failures**: Ensure JWT tokens are properly generated and server globals are set correctly.
-   **Header Validation**: Streamable transport requires specific Accept headers.
-   **Permission Issues**: Remember that Streamable requires `manage_options` capability.

### Debug Tips

-   Use `error_log()` to debug request/response data.
-   Check that MCP is enabled with `get_option('wpmcp_enabled')`.
-   Verify user roles and capabilities in test setup.
-   Ensure REST API is properly initialized with `do_action('rest_api_init')`.

For more information about WordPress testing, see the [WordPress Testing Documentation](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/).

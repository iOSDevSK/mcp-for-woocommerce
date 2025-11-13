<?php // phpcs:ignore
/**
 * JWT Authentication implementation.
 *
 * @package WordPress_MCP
 * @subpackage Auth
 */

declare(strict_types=1);

namespace McpForWoo\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Exception;

/**
 * Class JwtAuth
 *
 * Handles JWT authentication for WordPress REST API.
 */
class JwtAuth {
	/**
	 * Option name for storing JWT secret key.
	 *
	 * @var string
	 */
	private const JWT_SECRET_KEY_OPTION = 'mcpfowo_jwt_secret_key';

	/**
	 * Default access token expiration time in seconds.
	 *
	 * @var int
	 */
	private const JWT_ACCESS_EXP_DEFAULT = 3600; // 1 hour.

	/**
	 * Minimum access token expiration time in seconds.
	 *
	 * @var int
	 */
	private const JWT_ACCESS_EXP_MIN = 3600; // 1 hour.

	/**
	 * Maximum access token expiration time in seconds.
	 *
	 * @var int
	 */
	private const JWT_ACCESS_EXP_MAX = 86400; // 1 day.

	/**
	 * Never expire constant.
	 *
	 * @var string
	 */
	private const JWT_NEVER_EXPIRE = 'never';

	/**
	 * Maximum number of active tokens per user.
	 *
	 * @var int
	 */
	private const MAX_ACTIVE_TOKENS = 10;

	/**
	 * Option name for storing active tokens.
	 *
	 * @var string
	 */
	private const TOKEN_REGISTRY_OPTION = 'mcpfowo_jwt_token_registry';

	/**
	 * Option name for storing authorization codes.
	 *
	 * @var string
	 */
	private const AUTH_CODE_OPTION = 'mcpfowo_oauth_auth_codes';

	/**
	 * Option name for storing registered clients.
	 *
	 * @var string
	 */
	private const REGISTERED_CLIENTS_OPTION = 'mcpfowo_oauth_clients';

	/**
	 * MCP endpoint path pattern for authentication.
	 *
	 * @var string
	 */
	private const MCP_ENDPOINT_PATTERN = '/wp/v2/wpmcp';

	/**
	 * Basic authentication pattern.
	 *
	 * @var string
	 */
	private const BASIC_AUTH_PATTERN = '/^Basic\s/';

	/**
	 * Bearer token pattern.
	 *
	 * @var string
	 */
	private const BEARER_TOKEN_PATTERN = '/Bearer\s(\S+)/';

	/**
	 * Check if JWT authentication is required.
	 *
	 * @return bool
	 */
	private function is_jwt_required(): bool {
		// Check if WordPress functions are available
		if ( ! function_exists( 'get_option' ) ) {
			// WordPress not fully loaded yet, default to require JWT
			return true;
		}
		return (bool) get_option( 'mcpfowo_jwt_required', true );
	}

	/**
	 * Get JWT secret key from options or generate a new one if not exists.
	 *
	 * @return string
	 */
	private function get_jwt_secret_key(): string {
		$key = get_option( self::JWT_SECRET_KEY_OPTION );

		if ( empty( $key ) ) {
			// Generate a new random key if none exists.
			$key = wp_generate_password( 64, true, true );
			update_option( self::JWT_SECRET_KEY_OPTION, $key );
		}

		return $key;
	}

	/**
	 * Initialize the JWT authentication.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'init', array( $this, 'maybe_register_auth_filter' ) );
		add_action( 'template_redirect', array( $this, 'handle_oauth_discovery' ) );
	}

	/**
	 * Conditionally register the authentication filter based on JWT requirement setting.
	 */
	public function maybe_register_auth_filter(): void {
		// Only register the auth filter if JWT is required
		if ( $this->is_jwt_required() ) {
			add_filter( 'rest_authentication_errors', array( $this, 'authenticate_request' ) );
		}
	}

	/**
	 * Register REST API routes for JWT authentication.
	 */
	public function register_routes(): void {
		register_rest_route(
			'jwt-auth/v1',
			'/token',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_jwt_token' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'username'   => array(
						'type'        => 'string',
						'description' => 'Username for authentication',
						'required'    => false,
					),
					'password'   => array(
						'type'        => 'string',
						'description' => 'Password for authentication',
						'required'    => false,
					),
					'expires_in' => array(
						//'type'        => 'string',
						'description' => 'Token expiration time in seconds (3600-86400) or "never" for no expiration',
						'required'    => false,
						'default'     => self::JWT_ACCESS_EXP_DEFAULT,
					),
				),
			)
		);

		register_rest_route(
			'jwt-auth/v1',
			'/revoke',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'revoke_token' ),
				'permission_callback' => array( $this, 'check_revoke_permission' ),
			)
		);

		register_rest_route(
			'jwt-auth/v1',
			'/tokens',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_tokens' ),
				'permission_callback' => array( $this, 'check_revoke_permission' ),
			)
		);

		// Register dynamic client registration endpoint
		register_rest_route(
			'jwt-auth/v1',
			'/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'register_client' ),
				'permission_callback' => '__return_true',
			)
		);

		// Register authorization endpoint for OAuth code flow
		register_rest_route(
			'jwt-auth/v1',
			'/authorize',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'authorize' ),
				'permission_callback' => '__return_true',
			)
		);

		// Register authorization submit endpoint
		register_rest_route(
			'jwt-auth/v1',
			'/authorize-submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'authorize_submit' ),
				'permission_callback' => '__return_true',
			)
		);

	}

	/**
	 * Handle OAuth discovery endpoint request.
	 */
	public function handle_oauth_discovery(): void {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( $request_uri === '/.well-known/oauth-authorization-server' ) {
			$site_url = get_bloginfo( 'url' );

			$discovery_data = array(
				'issuer'                    => $site_url,
				'authorization_endpoint'    => $site_url . '/wp-json/jwt-auth/v1/authorize',
				'token_endpoint'            => $site_url . '/wp-json/jwt-auth/v1/token',
				'registration_endpoint'     => $site_url . '/wp-json/jwt-auth/v1/register',
				'response_types_supported'  => array( 'code', 'token' ),
				'grant_types_supported'     => array( 'authorization_code', 'password', 'client_credentials' ),
				'token_endpoint_auth_methods_supported' => array( 'client_secret_basic', 'client_secret_post' ),
				'code_challenge_methods_supported' => array( 'S256', 'plain' ),
				'ai_plugin_url'             => $site_url . '/.well-known/ai-plugin.json',
			);

			header( 'Content-Type: application/json' );
			echo wp_json_encode( $discovery_data );
			exit;
		}
	}

	/**
	 * Register a dynamic client for OAuth.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function register_client( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		// Generate client credentials
		$client_id = 'mcp_' . wp_generate_password( 32, false );
		$client_secret = wp_generate_password( 64, true, true );
		$client_secret_hash = wp_hash_password( $client_secret );

		// Get redirect_uris from request or use default
		$redirect_uris = isset( $params['redirect_uris'] ) ? $params['redirect_uris'] : array();

		// Store client registration
		$clients = get_option( self::REGISTERED_CLIENTS_OPTION, array() );
		$clients[ $client_id ] = array(
			'client_secret_hash' => $client_secret_hash,
			'redirect_uris'      => $redirect_uris,
			'created_at'         => time(),
		);
		update_option( self::REGISTERED_CLIENTS_OPTION, $clients );

		$response = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'client_id_issued_at' => time(),
			'grant_types'   => array( 'authorization_code', 'password', 'client_credentials' ),
			'token_endpoint_auth_method' => 'client_secret_post',
			'redirect_uris' => $redirect_uris,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * OAuth authorize endpoint - handles authorization code flow.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function authorize( WP_REST_Request $request ) {
		$client_id = $request->get_param( 'client_id' );
		$redirect_uri = $request->get_param( 'redirect_uri' );
		$response_type = $request->get_param( 'response_type' );
		$state = $request->get_param( 'state' );
		$code_challenge = $request->get_param( 'code_challenge' );
		$code_challenge_method = $request->get_param( 'code_challenge_method' );

		// Validate client
		$clients = get_option( self::REGISTERED_CLIENTS_OPTION, array() );
		if ( ! isset( $clients[ $client_id ] ) ) {
			return new WP_Error( 'invalid_client', 'Invalid client_id', array( 'status' => 400 ) );
		}

		// Validate redirect_uri
		$client = $clients[ $client_id ];
		if ( ! in_array( $redirect_uri, $client['redirect_uris'], true ) ) {
			return new WP_Error( 'invalid_redirect_uri', 'Invalid redirect_uri', array( 'status' => 400 ) );
		}

		// Check if user is logged in as admin
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			// Return HTML form for authentication
			$this->render_authorization_form( $request );
			exit;
		}

		// Generate authorization code
		$code = wp_generate_password( 32, false );
		$auth_codes = get_option( self::AUTH_CODE_OPTION, array() );
		$auth_codes[ $code ] = array(
			'client_id'             => $client_id,
			'user_id'               => get_current_user_id(),
			'redirect_uri'          => $redirect_uri,
			'expires_at'            => time() + 600, // 10 minutes
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => $code_challenge_method,
		);
		update_option( self::AUTH_CODE_OPTION, $auth_codes );

		// Redirect back to client with authorization code
		$redirect_url = add_query_arg(
			array(
				'code'  => $code,
				'state' => $state,
			),
			$redirect_uri
		);

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render authorization form for OAuth flow.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	private function render_authorization_form( WP_REST_Request $request ): void {
		$site_name = get_bloginfo( 'name' );
		$authorize_url = rest_url( 'jwt-auth/v1/authorize-submit' );

		// Get all query params
		$query_params = $request->get_query_params();

		header( 'Content-Type: text/html; charset=utf-8' );
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Authorize Access - <?php echo esc_html( $site_name ); ?></title>
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f0f1; margin: 0; padding: 20px; }
				.container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
				h1 { margin: 0 0 20px; font-size: 24px; color: #1d2327; }
				.info { background: #f0f6fc; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; color: #1d2327; }
				label { display: block; margin-bottom: 5px; font-weight: 500; color: #1d2327; }
				input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #dcdcde; border-radius: 4px; box-sizing: border-box; margin-bottom: 15px; }
				button { width: 100%; padding: 12px; background: #2271b1; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
				button:hover { background: #135e96; }
				.error { background: #fcf0f1; color: #d63638; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
			</style>
		</head>
		<body>
			<div class="container">
				<h1>Authorize Access</h1>
				<div class="info">
					<strong><?php echo esc_html( $site_name ); ?></strong> is requesting access to your account.
				</div>
				<form method="POST" action="<?php echo esc_url( $authorize_url ); ?>">
					<?php foreach ( $query_params as $key => $value ) : ?>
						<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
					<?php endforeach; ?>

					<label for="username">Username</label>
					<input type="text" id="username" name="username" required autocomplete="username">

					<label for="password">Password</label>
					<input type="password" id="password" name="password" required autocomplete="current-password">

					<button type="submit">Authorize</button>
				</form>
			</div>
		</body>
		</html>
		<?php
	}

	/**
	 * Handle authorization form submission.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function authorize_submit( WP_REST_Request $request ) {
		$username = $request->get_param( 'username' );
		$password = $request->get_param( 'password' );
		$client_id = $request->get_param( 'client_id' );
		$redirect_uri = $request->get_param( 'redirect_uri' );
		$state = $request->get_param( 'state' );
		$code_challenge = $request->get_param( 'code_challenge' );
		$code_challenge_method = $request->get_param( 'code_challenge_method' );

		// Authenticate user
		$user = wp_authenticate( $username, $password );
		if ( is_wp_error( $user ) ) {
			wp_die( 'Invalid username or password. <a href="javascript:history.back()">Go back</a>' );
		}

		// Check if user has admin capabilities
		if ( ! user_can( $user->ID, 'manage_options' ) ) {
			wp_die( 'You do not have permission to authorize this application. Only administrators can authorize access.' );
		}

		// Generate authorization code
		$code = wp_generate_password( 32, false );
		$auth_codes = get_option( self::AUTH_CODE_OPTION, array() );
		$auth_codes[ $code ] = array(
			'client_id'             => $client_id,
			'user_id'               => $user->ID,
			'redirect_uri'          => $redirect_uri,
			'expires_at'            => time() + 600, // 10 minutes
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => $code_challenge_method,
		);
		update_option( self::AUTH_CODE_OPTION, $auth_codes );

		// Redirect back to client with authorization code
		$redirect_url = add_query_arg(
			array(
				'code'  => $code,
				'state' => $state,
			),
			$redirect_uri
		);

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Check if the current user has permission to manage tokens.
	 *
	 * @return bool|WP_Error
	 */
	public function check_revoke_permission() {
		// First check if user is logged in and has manage_options capability
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return true;
		}

		// If not authenticated via cookies, return error with details
		return new WP_Error(
			'rest_forbidden',
			__( 'You need to be logged in as an administrator to access JWT tokens.', 'mcp-for-woocommerce' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Generate JWT token for authenticated user.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_jwt_token( WP_REST_Request $request ) {
		$params     = $request->get_json_params();
		$grant_type = isset( $params['grant_type'] ) ? $params['grant_type'] : 'password';
		$expires_in = isset( $params['expires_in'] ) ? $params['expires_in'] : self::JWT_ACCESS_EXP_DEFAULT;

		// Handle authorization_code grant type
		if ( $grant_type === 'authorization_code' ) {
			return $this->handle_authorization_code_grant( $params, $expires_in );
		}

		// If user is already authenticated, use their ID.
		if ( is_user_logged_in() ) {
			$result = $this->generate_token( get_current_user_id(), $expires_in );
			if ( is_wp_error( $result ) ) {
				return $this->oauth_error_response( 'server_error', $result->get_error_message() );
			}
			return rest_ensure_response( $result );
		}

		// Otherwise, try to authenticate with provided credentials.
		$username = isset( $params['username'] ) ? sanitize_text_field( $params['username'] ) : '';
		$password = isset( $params['password'] ) ? $params['password'] : '';

		$user = wp_authenticate( $username, $password );
		if ( is_wp_error( $user ) ) {
			return $this->oauth_error_response( 'invalid_grant', 'Invalid username or password', 401 );
		}

		$result = $this->generate_token( $user->ID, $expires_in );
		if ( is_wp_error( $result ) ) {
			return $this->oauth_error_response( 'server_error', $result->get_error_message() );
		}
		return rest_ensure_response( $result );
	}

	/**
	 * Return OAuth-compliant error response.
	 *
	 * @param string $error Error code.
	 * @param string $error_description Error description.
	 * @param int    $status HTTP status code.
	 * @return WP_REST_Response
	 */
	private function oauth_error_response( string $error, string $error_description, int $status = 400 ): WP_REST_Response {
		$response = new WP_REST_Response(
			array(
				'error'             => $error,
				'error_description' => $error_description,
			),
			$status
		);
		return $response;
	}

	/**
	 * Handle authorization code grant.
	 *
	 * @param array      $params Request parameters.
	 * @param string|int $expires_in Token expiration.
	 * @return WP_REST_Response|WP_Error
	 */
	private function handle_authorization_code_grant( array $params, $expires_in ) {
		$code = isset( $params['code'] ) ? $params['code'] : '';
		$client_id = isset( $params['client_id'] ) ? $params['client_id'] : '';
		$client_secret = isset( $params['client_secret'] ) ? $params['client_secret'] : '';
		$redirect_uri = isset( $params['redirect_uri'] ) ? $params['redirect_uri'] : '';
		$code_verifier = isset( $params['code_verifier'] ) ? $params['code_verifier'] : '';

		// Validate client credentials
		$clients = get_option( self::REGISTERED_CLIENTS_OPTION, array() );
		if ( ! isset( $clients[ $client_id ] ) ) {
			return $this->oauth_error_response( 'invalid_client', 'Invalid client credentials', 401 );
		}

		$client = $clients[ $client_id ];
		if ( ! wp_check_password( $client_secret, $client['client_secret_hash'] ) ) {
			return $this->oauth_error_response( 'invalid_client', 'Invalid client credentials', 401 );
		}

		// Validate authorization code
		$auth_codes = get_option( self::AUTH_CODE_OPTION, array() );
		if ( ! isset( $auth_codes[ $code ] ) ) {
			return $this->oauth_error_response( 'invalid_grant', 'Invalid authorization code', 400 );
		}

		$auth_code = $auth_codes[ $code ];

		// Check if code is expired
		if ( time() > $auth_code['expires_at'] ) {
			unset( $auth_codes[ $code ] );
			update_option( self::AUTH_CODE_OPTION, $auth_codes );
			return $this->oauth_error_response( 'invalid_grant', 'Authorization code expired', 400 );
		}

		// Validate client_id and redirect_uri match
		if ( $auth_code['client_id'] !== $client_id || $auth_code['redirect_uri'] !== $redirect_uri ) {
			return $this->oauth_error_response( 'invalid_grant', 'Invalid authorization code', 400 );
		}

		// Validate PKCE if code_challenge was provided
		if ( ! empty( $auth_code['code_challenge'] ) ) {
			$method = $auth_code['code_challenge_method'] ?? 'plain';
			$challenge = $method === 'S256'
				? rtrim( strtr( base64_encode( hash( 'sha256', $code_verifier, true ) ), '+/', '-_' ), '=' )
				: $code_verifier;

			if ( $challenge !== $auth_code['code_challenge'] ) {
				return $this->oauth_error_response( 'invalid_grant', 'Invalid code_verifier', 400 );
			}
		}

		// Generate token for the user
		$user_id = $auth_code['user_id'];

		// Delete the authorization code (one-time use)
		unset( $auth_codes[ $code ] );
		update_option( self::AUTH_CODE_OPTION, $auth_codes );

		$result = $this->generate_token( $user_id, $expires_in );
		if ( is_wp_error( $result ) ) {
			return $this->oauth_error_response( 'server_error', $result->get_error_message() );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Generate access token.
	 *
	 * @param int        $user_id The user ID.
	 * @param string|int $expires_in Token expiration time in seconds or "never".
	 * @return array|WP_Error
	 */
	private function generate_token( int $user_id, $expires_in = self::JWT_ACCESS_EXP_DEFAULT ) {
		// Clean up tokens and check if we can generate a new one
		$active_token_count = $this->cleanup_tokens_for_user( $user_id );
		
		// Check if we can generate a new token
		if ( $active_token_count >= self::MAX_ACTIVE_TOKENS ) {
			return new WP_Error(
				'token_limit_exceeded',
				sprintf(
					'Maximum of %d active tokens allowed per user. Please revoke some existing tokens before generating new ones.',
					self::MAX_ACTIVE_TOKENS
				),
				array( 'status' => 429 ) // 429 Too Many Requests
			);
		}
		
		$issued_at = time();
		$jti       = wp_generate_password( 32, false );
		
		// Process expiration
		$never_expire = false;
		$expires_at = null;
		
		if ( $expires_in === self::JWT_NEVER_EXPIRE || $expires_in === 'never' ) {
			$never_expire = true;
			// For never expire, set very distant date (100 years from now)
			$expires_at = $issued_at + (100 * 365 * 24 * 60 * 60);
			$expires_in_seconds = null; // Don't show expires_in for never expire
		} else {
			$expires_in_seconds = intval( $expires_in );
			
			// Validate expiration time for normal tokens
			if ( $expires_in_seconds < self::JWT_ACCESS_EXP_MIN || $expires_in_seconds > self::JWT_ACCESS_EXP_MAX ) {
				return new WP_Error(
					'invalid_expiration',
					sprintf(
						'Token expiration must be between %d seconds (1 hour) and %d seconds (1 day), or "never"',
						self::JWT_ACCESS_EXP_MIN,
						self::JWT_ACCESS_EXP_MAX
					),
					array( 'status' => 400 )
				);
			}
			
			$expires_at = $issued_at + $expires_in_seconds;
		}

		$payload = array(
			'iss'     => get_bloginfo( 'url' ),
			'iat'     => $issued_at,
			'user_id' => $user_id,
			'jti'     => $jti,
		);
		
		// Add exp claim only if not never expire
		if ( ! $never_expire ) {
			$payload['exp'] = $expires_at;
		}

		$token = JWT::encode( $payload, $this->get_jwt_secret_key(), 'HS256' );

		// Register the token
		$this->register_token( $jti, $user_id, $issued_at, $expires_at, $never_expire );

		$response = array(
			'access_token' => $token,
			'token_type'   => 'Bearer',
			'user_id'      => $user_id,
			'expires_at'   => $expires_at,
		);

		if ( $never_expire ) {
			$response['expires_in'] = 'never';
			$response['never_expire'] = true;
		} else {
			$response['expires_in'] = $expires_in_seconds;
		}

		return $response;
	}

	/**
	 * Register a new token in the registry.
	 *
	 * @param string $jti Token ID.
	 * @param int    $user_id User ID.
	 * @param int    $issued_at Token issued timestamp.
	 * @param int    $expires_at Token expiration timestamp.
	 * @param bool   $never_expire Whether token never expires.
	 */
	private function register_token( string $jti, int $user_id, int $issued_at, int $expires_at, bool $never_expire = false ): void {
		$registry = get_option( self::TOKEN_REGISTRY_OPTION, array() );

		$registry[ $jti ] = array(
			'user_id'      => $user_id,
			'issued_at'    => $issued_at,
			'expires_at'   => $expires_at,
			'never_expire' => $never_expire,
			'revoked'      => false,
		);

		update_option( self::TOKEN_REGISTRY_OPTION, $registry );
	}

	/**
	 * Clean up tokens for a specific user to maintain the maximum limit.
	 * Removes oldest revoked tokens first.
	 *
	 * @param int $user_id User ID.
	 * @return int Number of active tokens after cleanup.
	 */
	private function cleanup_tokens_for_user( int $user_id ): int {
		$registry = get_option( self::TOKEN_REGISTRY_OPTION, array() );
		$current_time = time();
		
		// Get user's tokens
		$user_tokens = array();
		foreach ( $registry as $jti => $token_data ) {
			if ( $token_data['user_id'] === $user_id ) {
				$user_tokens[ $jti ] = $token_data;
			}
		}
		
		// Separate revoked and active tokens
		$revoked_tokens = array();
		$active_tokens = array();
		
		foreach ( $user_tokens as $jti => $token_data ) {
			// Check if token is expired (for non-never-expire tokens)
			$is_never_expire = isset( $token_data['never_expire'] ) && $token_data['never_expire'];
			$is_expired = ! $is_never_expire && $current_time > $token_data['expires_at'];
			
			if ( $token_data['revoked'] || $is_expired ) {
				$revoked_tokens[ $jti ] = $token_data;
			} else {
				$active_tokens[ $jti ] = $token_data;
			}
		}
		
		// Clean up revoked/expired tokens
		if ( ! empty( $revoked_tokens ) ) {
			// Sort by issued_at (oldest first)
			uasort( $revoked_tokens, function( $a, $b ) {
				return $a['issued_at'] - $b['issued_at'];
			});
			
			// Remove all revoked/expired tokens
			foreach ( $revoked_tokens as $jti => $token_data ) {
				unset( $registry[ $jti ] );
			}
			
			update_option( self::TOKEN_REGISTRY_OPTION, $registry );
		}
		
		return count( $active_tokens );
	}

	/**
	 * Revoke a JWT token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function revoke_token( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$jti    = isset( $params['jti'] ) ? $params['jti'] : '';

		if ( empty( $jti ) ) {
			return new WP_Error(
				'missing_jti',
				'Token ID is required.',
				array( 'status' => 400 )
			);
		}

		$registry = get_option( self::TOKEN_REGISTRY_OPTION, array() );

		if ( ! isset( $registry[ $jti ] ) ) {
			return new WP_Error(
				'token_not_found',
				'Token not found in registry.',
				array( 'status' => 404 )
			);
		}

		$registry[ $jti ]['revoked'] = true;
		update_option( self::TOKEN_REGISTRY_OPTION, $registry );

		return rest_ensure_response(
			array(
				'message' => 'Token revoked successfully.',
			)
		);
	}

	/**
	 * List all active tokens.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function list_tokens( WP_REST_Request $request ) {
		$registry     = get_option( self::TOKEN_REGISTRY_OPTION, array() );
		$tokens       = array();
		$current_time = time();
		$has_changes  = false;

		foreach ( $registry as $jti => $token_data ) {
			// For never expire tokens, don't check expiration
			$is_never_expire = isset( $token_data['never_expire'] ) && $token_data['never_expire'];
			
			// Skip and remove expired tokens (only those that are not never expire)
			if ( ! $is_never_expire && $current_time > $token_data['expires_at'] ) {
				unset( $registry[ $jti ] );
				$has_changes = true;
				continue;
			}

			$user = get_user_by( 'id', $token_data['user_id'] );
			if ( ! $user ) {
				unset( $registry[ $jti ] );
				$has_changes = true;
				continue;
			}

			$tokens[] = array(
				'jti'          => $jti,
				'user'         => array(
					'id'           => $user->ID,
					'username'     => $user->user_login,
					'display_name' => $user->display_name,
				),
				'issued_at'    => $token_data['issued_at'],
				'expires_at'   => $token_data['expires_at'],
				'never_expire' => $is_never_expire,
				'revoked'      => $token_data['revoked'],
				'is_expired'   => $is_never_expire ? false : ($current_time > $token_data['expires_at']),
			);
		}

		// Update the registry if we removed any tokens
		if ( $has_changes ) {
			update_option( self::TOKEN_REGISTRY_OPTION, $registry );
		}

		return rest_ensure_response( $tokens );
   }

   /**
    * Check if a token is valid.
    *
    * @param string $jti The token ID.
    * @return bool
    */
   private function is_token_valid( string $jti ): bool {
   	$registry = get_option( self::TOKEN_REGISTRY_OPTION, array() );

   	if ( ! isset( $registry[ $jti ] ) ) {
   		return false;
   	}

   	$token_data = $registry[ $jti ];

   	// Check if token is revoked
   	if ( $token_data['revoked'] ) {
   		return false;
   	}
   	
   	// If token is set to never expire, only check revoked status
   	if ( isset( $token_data['never_expire'] ) && $token_data['never_expire'] ) {
   		return true;
   	}

   	// For normal tokens, check expiration
   	if ( time() > $token_data['expires_at'] ) {
   		return false;
   	}

   	return true;
   }

   /**
    * Check if the current request is for an MCP endpoint.
    *
    * @return bool
    */
   private function is_mcp_endpoint(): bool {
   	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
   	return str_contains( $request_uri, self::MCP_ENDPOINT_PATTERN );
   }

   /**
    * Get Authorization header from request.
    *
    * @return string
    */
   private function get_authorization_header(): string {
   	return isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : '';
   }

   /**
    * Check if the authorization header contains Basic authentication.
    *
    * @param string $auth Authorization header value.
    * @return bool
    */
   private function is_basic_auth( string $auth ): bool {
   	return ! empty( $auth ) && preg_match( self::BASIC_AUTH_PATTERN, $auth );
   }

   /**
    * Extract Bearer token from authorization header.
    *
    * @param string $auth Authorization header value.
    * @return string|null Token if found, null otherwise.
    */
   private function extract_bearer_token( string $auth ): ?string {
   	// Try with Bearer prefix first
   	if ( preg_match( self::BEARER_TOKEN_PATTERN, $auth, $matches ) ) {
   		return $matches[1];
   	}

   	// If no Bearer prefix, check if the whole string looks like a JWT token
   	// JWT tokens have format: xxx.yyy.zzz (3 base64 parts separated by dots)
   	if ( preg_match( '/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $auth ) ) {
   		return $auth;
   	}

   	return null;
   }

   /**
    * Check if cookie-based authentication is valid for MCP endpoints.
    *
    * @return bool
    */
   private function is_valid_cookie_auth(): bool {
   	// Only allow cookie auth for logged-in users with manage_options capability
   	// This provides a secure fallback for admin users
   	return is_user_logged_in() && current_user_can( 'manage_options' );
   }

   /**
    * Log authentication events for security monitoring.
    *
    * @param string $event Event type.
    * @param string $details Event details.
    */
   private function log_auth_event( string $event, string $details ): void {
   	// Only log if WP_DEBUG is enabled to avoid filling logs in production
   	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
   		// Use error_log for better performance than custom logging
   		$log_message = sprintf(
   			'[WPMCP JWT Auth] %s: %s (IP: %s, URI: %s)',
   			$event,
   			$details,
   			sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) ),
   			sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? 'unknown' ) )
   		);
   	}
   }

   /**
    * Authenticate REST API request using JWT token.
    *
    * @param mixed $result The authentication result.
    * @return mixed
    * @throws Exception When token validation fails.
    */
   public function authenticate_request( $result ) {
   	// If already authenticated, return early.
   	if ( ! empty( $result ) ) {
   		return $result;
   	}

   	// Only apply JWT authentication to MCP endpoints.
   	if ( ! $this->is_mcp_endpoint() ) {
   		return $result;
   	}

   	// Skip JWT authentication if it's disabled - return original result to avoid interfering
   	if ( ! $this->is_jwt_required() ) {
   		$this->log_auth_event( 'JWT_DISABLED', 'JWT authentication is disabled - passing through' );
   		return $result;
   	}

   	$auth = $this->get_authorization_header();

   	// Handle Basic authentication - let it pass through to WordPress core handlers.
   	if ( $this->is_basic_auth( $auth ) ) {
   		$this->log_auth_event( 'BASIC_AUTH_DETECTED', 'Deferring to Basic auth handler' );
   		return $result;
   	}

   	// Handle missing Authorization header.
   	if ( empty( $auth ) ) {
   		return $this->handle_missing_authorization();
   	}

   	// Handle Bearer token authentication.
   	return $this->handle_bearer_token( $auth );
   }

   /**
    * Handle authentication when no Authorization header is present.
    *
    * @return mixed Authentication result.
    */
   private function handle_missing_authorization() {
   	// Fallback to cookie authentication for admin users.
   	if ( $this->is_valid_cookie_auth() ) {
   		$this->log_auth_event( 'COOKIE_AUTH_SUCCESS', 'Admin user authenticated via cookies' );
   		return true;
   	}

   	$this->log_auth_event( 'AUTH_REQUIRED', 'No valid authentication method found' );
   	return new WP_Error(
   		'unauthorized',
   		'Authentication required. Please provide a Bearer token or log in as an administrator.',
   		array( 'status' => 401 )
   	);
   }

   /**
    * Handle Bearer token authentication.
    *
    * @param string $auth Authorization header value.
    * @return mixed Authentication result.
    */
   private function handle_bearer_token( string $auth ) {
   	$token = $this->extract_bearer_token( $auth );

   	if ( null === $token ) {
   		$this->log_auth_event( 'INVALID_AUTH_FORMAT', 'Authorization header present but not Bearer token' );
   		return new WP_Error(
   			'unauthorized',
   			'Invalid Authorization header format. Expected "Bearer <token>".',
   			array( 'status' => 401 )
   		);
   	}

   	return $this->validate_jwt_token( $token );
   }

   /**
    * Validate JWT token and authenticate user.
    *
    * @param string $token JWT token.
    * @return mixed Authentication result.
    */
   private function validate_jwt_token( string $token ) {
   	try {
   		$decoded = JWT::decode( $token, new Key( $this->get_jwt_secret_key(), 'HS256' ) );

   		// Validate token ID
   		if ( ! isset( $decoded->jti ) || ! $this->is_token_valid( $decoded->jti ) ) {
   			$this->log_auth_event( 'TOKEN_INVALID', 'Token is invalid, expired, or revoked' );
   			return new WP_Error(
   				'token_invalid',
   				'Token is invalid, expired, or has been revoked.',
   				array( 'status' => 401 )
   			);
   		}

   		// Validate user
   		if ( ! isset( $decoded->user_id ) ) {
   			$this->log_auth_event( 'TOKEN_MALFORMED', 'Token missing user_id claim' );
   			return new WP_Error(
   				'invalid_token',
   				'Token is malformed: missing user_id.',
   				array( 'status' => 403 )
   			);
   		}

   		$user = get_user_by( 'id', $decoded->user_id );
   		if ( ! $user ) {
   			$this->log_auth_event( 'USER_NOT_FOUND', "User ID {$decoded->user_id} not found" );
   			return new WP_Error(
   				'invalid_token',
   				'User associated with token no longer exists.',
   				array( 'status' => 403 )
   			);
   		}

   		// Set current user
   		wp_set_current_user( $user->ID );
   		$this->log_auth_event( 'JWT_AUTH_SUCCESS', "User {$user->user_login} authenticated via JWT" );

   		return true;

   	} catch ( Exception $e ) {
   		$this->log_auth_event( 'JWT_DECODE_ERROR', $e->getMessage() );
   		return new WP_Error(
   			'invalid_token',
   			'Token validation failed: ' . $e->getMessage(),
   			array( 'status' => 403 )
   		);
   	}
   }
}

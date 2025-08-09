<?php // phpcs:ignore
/**
 * JWT Authentication implementation.
 *
 * @package WordPress_MCP
 * @subpackage Auth
 */

declare(strict_types=1);
namespace Automattic\WordpressMcp\Auth;

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
	private const JWT_SECRET_KEY_OPTION = 'wpmcp_jwt_secret_key';

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
	 * Option name for storing active tokens.
	 *
	 * @var string
	 */
	private const TOKEN_REGISTRY_OPTION = 'jwt_token_registry';

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
		return (bool) get_option( 'wordpress_mcp_jwt_required', true );
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
			__( 'You need to be logged in as an administrator to access JWT tokens.', 'wordpress-mcp' ),
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
		$expires_in = isset( $params['expires_in'] ) ? $params['expires_in'] : self::JWT_ACCESS_EXP_DEFAULT;

		// If user is already authenticated, use their ID.
		if ( is_user_logged_in() ) {
			return rest_ensure_response( $this->generate_token( get_current_user_id(), $expires_in ) );
		}

		// Otherwise, try to authenticate with provided credentials.
		$username = isset( $params['username'] ) ? sanitize_text_field( $params['username'] ) : '';
		$password = isset( $params['password'] ) ? $params['password'] : '';

		$user = wp_authenticate( $username, $password );
		if ( is_wp_error( $user ) ) {
			return new WP_Error(
				'invalid_credentials',
				'Invalid username or password',
				array( 'status' => 403 )
			);
		}

		return rest_ensure_response( $this->generate_token( $user->ID, $expires_in ) );
	}

	/**
	 * Generate access token.
	 *
	 * @param int        $user_id The user ID.
	 * @param string|int $expires_in Token expiration time in seconds or "never".
	 * @return array
	 */
	private function generate_token( int $user_id, $expires_in = self::JWT_ACCESS_EXP_DEFAULT ): array {
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
			'token'      => $token,
			'user_id'    => $user_id,
			'expires_at' => $expires_at,
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
   	if ( preg_match( self::BEARER_TOKEN_PATTERN, $auth, $matches ) ) {
   		return $matches[1];
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
   			$_SERVER['REMOTE_ADDR'] ?? 'unknown',
   			$_SERVER['REQUEST_URI'] ?? 'unknown'
   		);
   		error_log( $log_message );
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

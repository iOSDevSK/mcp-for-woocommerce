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
	 * Option name for storing active tokens.
	 *
	 * @var string
	 */
	private const TOKEN_REGISTRY_OPTION = 'jwt_token_registry';

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
		add_filter( 'rest_authentication_errors', array( $this, 'authenticate_request' ) );
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
						'type'        => 'integer',
						'description' => 'Token expiration time in seconds (3600-86400)',
						'required'    => false,
						'minimum'     => self::JWT_ACCESS_EXP_MIN,
						'maximum'     => self::JWT_ACCESS_EXP_MAX,
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
	 * @return bool
	 */
	public function check_revoke_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Generate JWT token for authenticated user.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_jwt_token( WP_REST_Request $request ) {
		$params     = $request->get_json_params();
		$expires_in = isset( $params['expires_in'] ) ? intval( $params['expires_in'] ) : self::JWT_ACCESS_EXP_DEFAULT;

		// Validate expiration time.
		if ( $expires_in < self::JWT_ACCESS_EXP_MIN || $expires_in > self::JWT_ACCESS_EXP_MAX ) {
			return new WP_Error(
				'invalid_expiration',
				sprintf(
					'Token expiration must be between %d seconds (1 hour) and %d seconds (1 day)',
					self::JWT_ACCESS_EXP_MIN,
					self::JWT_ACCESS_EXP_MAX
				),
				array( 'status' => 400 )
			);
		}

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
	 * @param int $user_id The user ID.
	 * @param int $expires_in Token expiration time in seconds.
	 * @return array
	 */
	private function generate_token( int $user_id, int $expires_in = self::JWT_ACCESS_EXP_DEFAULT ): array {
		$issued_at  = time();
		$expires_at = $issued_at + $expires_in;
		$jti        = wp_generate_password( 32, false );

		$payload = array(
			'iss'     => get_bloginfo( 'url' ),
			'iat'     => $issued_at,
			'exp'     => $expires_at,
			'user_id' => $user_id,
			'jti'     => $jti,
		);

		$token = JWT::encode( $payload, $this->get_jwt_secret_key(), 'HS256' );

		// Register the token.
		$this->register_token( $jti, $user_id, $issued_at, $expires_at );

		return array(
			'token'      => $token,
			'user_id'    => $user_id,
			'expires_in' => $expires_in,
			'expires_at' => $expires_at,
		);
	}

	/**
	 * Register a new token in the registry.
	 *
	 * @param string $jti Token ID.
	 * @param int    $user_id User ID.
	 * @param int    $issued_at Token issued timestamp.
	 * @param int    $expires_at Token expiration timestamp.
	 */
	private function register_token( string $jti, int $user_id, int $issued_at, int $expires_at ): void {
		$registry = get_option( self::TOKEN_REGISTRY_OPTION, array() );

		$registry[ $jti ] = array(
			'user_id'    => $user_id,
			'issued_at'  => $issued_at,
			'expires_at' => $expires_at,
			'revoked'    => false,
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
			// Skip and remove expired tokens.
			if ( $current_time > $token_data['expires_at'] ) {
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
				'jti'        => $jti,
				'user'       => array(
					'id'           => $user->ID,
					'username'     => $user->user_login,
					'display_name' => $user->display_name,
				),
				'issued_at'  => $token_data['issued_at'],
				'expires_at' => $token_data['expires_at'],
				'revoked'    => $token_data['revoked'],
				'is_expired' => false,
			);
		}

		// Update the registry if we removed any tokens.
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

		// Check if token is revoked or expired.
		if ( $token_data['revoked'] || time() > $token_data['expires_at'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Authenticate REST API request using JWT token.
	 *
	 * @param mixed $result The authentication result.
	 * @return mixed
	 * @throws Exception When token validation fails.
	 */
	public function authenticate_request( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		// Apply JWT authentication to MCP endpoints.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( ! str_contains( $request_uri, '/wp/v2/wpmcp' ) ) {
			return $result;
		}

		$auth = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : '';
		if ( ! $auth || ! preg_match( '/Bearer\s(\S+)/', $auth, $matches ) ) {
			return new WP_Error(
				'unauthorized',
				'Missing or invalid Authorization header.',
				array( 'status' => 401 )
			);
		}

		$token = $matches[1];
		try {
			$decoded = JWT::decode( $token, new Key( $this->get_jwt_secret_key(), 'HS256' ) );

			// Check if token is valid.
			if ( ! $this->is_token_valid( $decoded->jti ) ) {
				return new WP_Error(
					'token_invalid',
					'Token is invalid or has been revoked.',
					array( 'status' => 401 )
				);
			}

			$user = get_user_by( 'id', $decoded->user_id );
			if ( ! $user ) {
				return new WP_Error(
					'invalid_token',
					'User not found.',
					array( 'status' => 403 )
				);
			}

			wp_set_current_user( $user->ID );

			return true;
		} catch ( Exception $e ) {
			return new WP_Error(
				'invalid_token',
				$e->getMessage(),
				array( 'status' => 403 )
			);
		}
	}
}

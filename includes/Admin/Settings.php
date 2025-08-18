<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Admin;

use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Core\McpProxyGenerator;

/**
 * Class Settings
 * Handles the MCP settings page in WordPress admin.
 */
class Settings {
	/**
	 * The option name in the WordPress options table.
	 */
	const OPTION_NAME = 'wordpress_mcp_settings';

	/**
	 * The tool states option name.
	 */
	const TOOL_STATES_OPTION = 'wordpress_mcp_tool_states';

	/**
	 * The JWT required option name.
	 */
	const JWT_REQUIRED_OPTION = 'wordpress_mcp_jwt_required';

	/**
	 * Initialize the settings page.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wordpress_mcp_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_wordpress_mcp_toggle_tool', array( $this, 'ajax_toggle_tool' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WORDPRESS_MCP_PATH . 'mcp-for-woocommerce.php' ), array( $this, 'plugin_action_links' ) );
		
		// Initialize JWT required option with default value if not exists
		add_action( 'init', array( $this, 'init_jwt_option' ) );
	}
	
	/**
	 * Initialize JWT required option with default value.
	 */
	public function init_jwt_option(): void {
		if ( false === get_option( self::JWT_REQUIRED_OPTION ) ) {
			add_option( self::JWT_REQUIRED_OPTION, true );
		}
	}

	/**
	 * Add the settings page to the WordPress admin menu.
	 */
	public function add_settings_page(): void {
		// Get plugin version from main plugin file header
		$plugin_data = get_file_data( WORDPRESS_MCP_PATH . 'mcp-for-woocommerce.php', array( 'Version' => 'Version' ) );
		$version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';
		
		// Create page title with version
		$page_title = trim( sprintf( 'MCP for WooCommerce %s', $version ) );
		
		add_options_page(
			$page_title,
			__( 'MCP for WooCommerce', 'mcp-for-woocommerce' ),
			'manage_options',
			'wordpress-mcp-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the settings and their sanitization callbacks.
	 */
	public function register_settings(): void {
		register_setting(
			'wordpress_mcp_settings',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}



	/**
	 * Enqueue scripts and styles for the React app.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( 'settings_page_wordpress-mcp-settings' !== $hook ) {
			return;
		}

		$asset_file = include WORDPRESS_MCP_PATH . 'build/index.asset.php';

		// Enqueue our React app.
		wp_enqueue_script(
			'wordpress-mcp-settings',
			WORDPRESS_MCP_URL . 'build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		// Enqueue the WordPress components styles CSS.
		wp_enqueue_style(
			'wp-components',
			includes_url( 'css/dist/components/style.css' ),
			array(),
			$asset_file['version'],
		);

		// Enqueue the WordPress MCP settings CSS.
		wp_enqueue_style(
			'wp-mcp-settings',
			WORDPRESS_MCP_URL . 'build/style-index.css',
			array(),
			$asset_file['version'],
		);

		// Localize the script with data needed by the React app.
		wp_localize_script(
			'wordpress-mcp-settings',
			'wordpressMcpSettings',
			array(
				'apiUrl'              => rest_url( 'wordpress-mcp/v1/settings' ),
				'jwtApiUrl'           => rest_url( 'jwt-auth/v1' ),
				'restFallbackUrl'     => home_url( '/index.php?rest_route=' ),
				'nonce'               => wp_create_nonce( 'wordpress_mcp_settings' ),
				'settings'            => get_option( self::OPTION_NAME, array() ),
				'toolStates'          => get_option( self::TOOL_STATES_OPTION, array() ),
				'jwtRequired'         => get_option( self::JWT_REQUIRED_OPTION, true ),
				'pluginUrl'           => WORDPRESS_MCP_URL,
				'claudeSetupInstructions' => McpProxyGenerator::should_generate_proxy() ? McpProxyGenerator::get_claude_setup_instructions() : null,
				'systemStatus'        => array(
					'restApiEnabled'   => $this->is_rest_api_enabled(),
					'permalinksCorrect' => $this->are_permalinks_correct(),
				),
				'strings'             => array(
					'enableMcp'                        => __( 'Enable MCP functionality', 'mcp-for-woocommerce' ),
					'enableMcpDescription'             => __( 'Toggle to enable or disable the MCP plugin functionality.', 'mcp-for-woocommerce' ),
					'saveSettings'                     => __( 'Save Settings', 'mcp-for-woocommerce' ),
					'settingsSaved'                    => __( 'Settings saved successfully!', 'mcp-for-woocommerce' ),
					'settingsError'                    => __( 'Error saving settings. Please try again.', 'mcp-for-woocommerce' ),
					// translators: %1$s is the tool name, %2$s is the status (enabled/disabled).
					'toolEnabled'                      => __( 'Tool %1$s has been %2$s.', 'mcp-for-woocommerce' ),
					// translators: %1$s is the tool name, %2$s is the status (enabled/disabled).
					'toolDisabled'                     => __( 'Tool %1$s has been %2$s.', 'mcp-for-woocommerce' ),

					'neverExpireWarning'               => __( 'Never-expiring tokens pose significant security risks. If compromised, they cannot be invalidated through expiration. Only use this option if you fully understand the security implications and have proper token management procedures in place.', 'mcp-for-woocommerce' ),
					'neverExpires'                     => __( 'Never expires', 'mcp-for-woocommerce' ),
					'activeNeverExpires'               => __( 'Active (Never expires)', 'mcp-for-woocommerce' ),
					'thisTokenNeverExpires'            => __( 'This token never expires', 'mcp-for-woocommerce' ),
					'securityWarning'                  => __( 'Security Warning', 'mcp-for-woocommerce' ),
					'neverExpiringTokens'              => __( 'Never-Expiring Tokens:', 'mcp-for-woocommerce' ),
					'requireJwtAuth'                   => __( 'Require JWT Authentication', 'mcp-for-woocommerce' ),
					'requireJwtAuthDescription'        => __( 'When enabled, all MCP requests must include a valid JWT token. When disabled, MCP endpoints are accessible without authentication (readonly mode only) and can be used as a Claude.ai Desktop connector.', 'mcp-for-woocommerce' ),
					'webtalkbotNote'                   => __( 'Note for Webtalkbot users:', 'mcp-for-woocommerce' ),
					'webtalkbotDescription'            => __( 'JWT Authentication must be enabled if you want to create a WooCommerce AI Agent in', 'mcp-for-woocommerce' ),
					'claudeConnectorNote'              => __( 'Claude.ai Desktop Connector:', 'mcp-for-woocommerce' ),
					'claudeConnectorDescription'       => __( 'When JWT Authentication is disabled, this plugin can be used as a connector in Claude.ai Desktop. A proxy file will be automatically generated for easy setup.', 'mcp-for-woocommerce' ),
					'proxyFileGenerated'               => __( 'MCP Proxy file generated at:', 'mcp-for-woocommerce' ),
					'claudeSetupInstructions'          => __( 'To use with Claude.ai Desktop, add this configuration to your claude_desktop_config.json:', 'mcp-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for saving settings.
	 */
	public function ajax_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'mcp-for-woocommerce' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wordpress_mcp_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'mcp-for-woocommerce' ) ) );
		}

		// Sanitize the settings input.
		$settings_raw = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '{}';
		$settings     = $this->sanitize_settings( json_decode( $settings_raw, true ) );
		update_option( self::OPTION_NAME, $settings );

		// Handle JWT required setting separately
		$jwt_required = isset( $_POST['jwt_required'] ) ? filter_var( wp_unslash( $_POST['jwt_required'] ), FILTER_VALIDATE_BOOLEAN ) : true;
		$old_jwt_required = get_option( self::JWT_REQUIRED_OPTION, true );
		update_option( self::JWT_REQUIRED_OPTION, $jwt_required );

		// Handle MCP proxy file generation/removal
		if ( $old_jwt_required !== $jwt_required ) {
			if ( ! $jwt_required ) {
				// JWT disabled - generate proxy file
				McpProxyGenerator::generate_proxy_file();
			} else {
				// JWT enabled - remove proxy file
				McpProxyGenerator::remove_proxy_file();
			}
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'mcp-for-woocommerce' ) ) );
	}

	/**
	 * Sanitize the settings before saving.
	 *
	 * @param array $input The input array.
	 * @return array The sanitized input array.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		if ( isset( $input['enabled'] ) ) {
			$sanitized['enabled'] = (bool) $input['enabled'];
		} else {
			$sanitized['enabled'] = false;
		}

		// Hardcode the removed settings for MCP for WooCommerce functionality
		$sanitized['features_adapter_enabled'] = false;     // WordPress Features Adapter disabled for MCP for WooCommerce
		$sanitized['enable_create_tools'] = true;           // Create tools always enabled for MCP for WooCommerce
		$sanitized['enable_update_tools'] = true;           // Update tools always enabled for MCP for WooCommerce
		$sanitized['enable_delete_tools'] = true;           // Delete tools always enabled for MCP for WooCommerce
		$sanitized['enable_rest_api_crud_tools'] = false;   // REST API CRUD tools always disabled for MCP for WooCommerce

		return $sanitized;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="wordpress-mcp-settings-app"></div>
		</div>
		<?php
	}

	/**
	 * Add settings link to plugin actions.
	 *
	 * @param array $actions An array of plugin action links.
	 * @return array
	 */
	public function plugin_action_links( array $actions ): array {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=wordpress-mcp-settings' ) . '">' . __( 'Settings', 'mcp-for-woocommerce' ) . '</a>';
		array_unshift( $actions, $settings_link );
		return $actions;
	}

	/**
	 * AJAX handler for toggling tool state.
	 */
	public function ajax_toggle_tool(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'mcp-for-woocommerce' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wordpress_mcp_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'mcp-for-woocommerce' ) ) );
		}

		$tool_name = isset( $_POST['tool'] ) ? sanitize_text_field( wp_unslash( $_POST['tool'] ) ) : '';
		$enabled   = isset( $_POST['tool_enabled'] ) ? filter_var( wp_unslash( $_POST['tool_enabled'] ), FILTER_VALIDATE_BOOLEAN ) : false;

		if ( empty( $tool_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Tool name is required.', 'mcp-for-woocommerce' ) ) );
		}

		$success = $this->toggle_tool( $tool_name, $enabled );

		if ( ! $success ) {
			wp_send_json_error( array( 'message' => __( 'Failed to toggle tool state.', 'mcp-for-woocommerce' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					// translators: %1$s is the tool name, %2$s is the status (enabled/disabled).
					__( 'Tool %1$s has been %2$s.', 'mcp-for-woocommerce' ),
					$tool_name,
					$enabled ? __( 'enabled', 'mcp-for-woocommerce' ) : __( 'disabled', 'mcp-for-woocommerce' )
				),
			)
		);
	}

	/**
	 * Toggle a tool's state.
	 *
	 * @param string $tool_name The name of the tool to toggle.
	 * @param bool   $enabled   Whether the tool should be enabled.
	 * @return bool Whether the operation was successful.
	 */
	public function toggle_tool( string $tool_name, bool $enabled ): bool {
		$tool_states               = get_option( self::TOOL_STATES_OPTION, array() );
		$tool_states[ $tool_name ] = $enabled;
		try {
			update_option( self::TOOL_STATES_OPTION, $tool_states, 'no' );
		} catch ( \Exception $e ) {
			// Log error only in debug mode
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			}
			return false;
		}
		return true;
	}

	/**
	 * Check if WordPress REST API is enabled.
	 *
	 * @return bool True if REST API is enabled, false otherwise.
	 */
	private function is_rest_api_enabled(): bool {
		// Try to make a simple REST API request
		$response = wp_remote_get( rest_url( 'wp/v2/types' ), array( 
			'timeout' => 5,
			'sslverify' => false 
		) );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		return ( $response_code === 200 );
	}

	/**
	 * Check if permalinks are set correctly (Post name structure).
	 *
	 * @return bool True if permalinks are correct, false otherwise.
	 */
	private function are_permalinks_correct(): bool {
		$permalink_structure = get_option( 'permalink_structure' );
		// Check if permalink structure is set to "Post name" (/%postname%/)
		return ( $permalink_structure === '/%postname%/' );
	}
}

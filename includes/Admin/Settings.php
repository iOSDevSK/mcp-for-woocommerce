<?php
declare(strict_types=1);


namespace McpForWoo\Admin;

use McpForWoo\Core\WpMcp;

/**
 * Class Settings
 * Handles the MCP settings page in WordPress admin.
 */
class Settings {
	/**
	 * The option name in the WordPress options table.
	 */
	const OPTION_NAME = 'mcpfowo_settings';

	/**
	 * The tool states option name.
	 */
	const TOOL_STATES_OPTION = 'mcpfowo_tool_states';

	/**
	 * Initialize the settings page.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_mcpfowo_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_mcpfowo_toggle_tool', array( $this, 'ajax_toggle_tool' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MCPFOWO_PATH . 'mcp-for-woocommerce.php' ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Add the settings page to the WordPress admin menu.
	 */
	public function add_settings_page(): void {
		// Get plugin version from main plugin file header
		$plugin_data = get_file_data( MCPFOWO_PATH . 'mcp-for-woocommerce.php', array( 'Version' => 'Version' ) );
		$version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';
		
		// Create page title with version
		$page_title = trim( sprintf( 'MCP for WooCommerce %s', $version ) );
		
		add_options_page(
			$page_title,
			__( 'MCP for WooCommerce', 'mcp-for-woocommerce' ),
			'manage_options',
			'mcpfowo-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the settings and their sanitization callbacks.
	 */
	public function register_settings(): void {
		register_setting(
			'mcpfowo_settings',
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
		if ( 'settings_page_mcpfowo-settings' !== $hook ) {
			return;
		}

		$asset_file = include MCPFOWO_PATH . 'build/index.asset.php';

		// Enqueue our React app.
		wp_enqueue_script(
			'mcpfowo-settings',
			MCPFOWO_URL . 'build/index.js',
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
			'mcpfowo-settings',
			MCPFOWO_URL . 'build/style-index.css',
			array(),
			$asset_file['version'],
		);

		// Localize the script with data needed by the React app.
		wp_localize_script(
			'mcpfowo-settings',
			'mcpfowoSettings',
			array(
				'mcpEndpoint'         => rest_url( 'wp/v2/wpmcp/streamable' ),
				'nonce'               => wp_create_nonce( 'mcpfowo_settings' ),
				'settings'            => get_option( self::OPTION_NAME, array() ),
				'toolStates'          => get_option( self::TOOL_STATES_OPTION, array() ),
				'pluginUrl'           => MCPFOWO_URL,
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

					'publicAccessNote'                 => __( 'The MCP endpoint is public. It returns only information that shop visitors can already see: published products, categories, tags, attributes, approved reviews, shipping and tax rates, enabled payment methods, and published posts and pages. It cannot create, change or delete anything, and it never signs in as a WordPress user.', 'mcp-for-woocommerce' ),
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
		if ( ! wp_verify_nonce( $nonce, 'mcpfowo_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'mcp-for-woocommerce' ) ) );
		}

		// Sanitize the settings input.
		$settings_raw = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '{}';
		$settings     = $this->sanitize_settings( json_decode( $settings_raw, true ) );
		update_option( self::OPTION_NAME, $settings );

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

		// Always store as integer (0 or 1) for consistency
		if ( isset( $input['enabled'] ) ) {
			$sanitized['enabled'] = $input['enabled'] ? 1 : 0;
		} else {
			$sanitized['enabled'] = 0;
		}

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
			<div id="mcpfowo-settings-app"></div>
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
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=mcpfowo-settings' ) . '">' . __( 'Settings', 'mcp-for-woocommerce' ) . '</a>';
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
		if ( ! wp_verify_nonce( $nonce, 'mcpfowo_settings' ) ) {
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
		// Always store as integer (0 or 1) for consistency
		$tool_states[ $tool_name ] = $enabled ? 1 : 0;
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
		$response = wp_remote_get( rest_url( 'wp/v2/types' ), array( 'timeout' => 5 ) );
		
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

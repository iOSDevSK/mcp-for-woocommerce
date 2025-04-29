<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Admin;

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
	 * Initialize the settings page.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wordpress_mcp_save_settings', array( $this, 'ajax_save_settings' ) );
	}

	/**
	 * Add the settings page to the WordPress admin menu.
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'MCP Settings', 'wordpress-mcp' ),
			__( 'MCP Settings', 'wordpress-mcp' ),
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
				'apiUrl'   => rest_url( 'wordpress-mcp/v1/settings' ),
				'nonce'    => wp_create_nonce( 'wordpress_mcp_settings' ),
				'settings' => get_option( self::OPTION_NAME, array() ),
				'strings'  => array(
					'enableMcp'                        => __( 'Enable MCP functionality', 'wordpress-mcp' ),
					'enableMcpDescription'             => __( 'Toggle to enable or disable the MCP plugin functionality.', 'wordpress-mcp' ),
					'enableFeaturesAdapter'            => __( 'Enable WordPress Features Adapter', 'wordpress-mcp' ),
					'enableFeaturesAdapterDescription' => __( 'Enable or disable the WordPress Features Adapter. This option only works when MCP is enabled.', 'wordpress-mcp' ),
					'saveSettings'                     => __( 'Save Settings', 'wordpress-mcp' ),
					'settingsSaved'                    => __( 'Settings saved successfully!', 'wordpress-mcp' ),
					'settingsError'                    => __( 'Error saving settings. Please try again.', 'wordpress-mcp' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for saving settings.
	 */
	public function ajax_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wordpress-mcp' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wordpress_mcp_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'wordpress-mcp' ) ) );
		}

		// Sanitize the settings input.
		$settings_raw = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '{}';
		$settings     = $this->sanitize_settings( json_decode( $settings_raw, true ) );
		update_option( self::OPTION_NAME, $settings );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'wordpress-mcp' ) ) );
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

		if ( isset( $input['features_adapter_enabled'] ) ) {
			$sanitized['features_adapter_enabled'] = (bool) $input['features_adapter_enabled'];
		} else {
			$sanitized['features_adapter_enabled'] = false;
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
			<div id="wordpress-mcp-settings-app"></div>
		</div>
		<?php
	}
}

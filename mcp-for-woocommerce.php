<?php
/**
 * Plugin name:       MCP for WooCommerce
 * Description:       Community-developed AI integration plugin that connects WooCommerce & WordPress with Model Context Protocol (MCP). Not affiliated with Automattic. Gives AI assistants read-only access to your public storefront: products, categories, reviews, shipping, payment methods, and published posts and pages. Acts as a WooCommerce MCP Server for MCP clients; pair with Webtalkbot to add a WooCommerce AI Chatbot/Agent to your site.
 * Version:           1.3.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * Author:            Filip Dvoran
 * Author URI:        https://github.com/iOSDevSK
 * Plugin URI:        https://github.com/iOSDevSK/mcp-for-woocommerce
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       mcp-for-woocommerce
 * Domain Path:       /languages
 *
 * @package WordPress MCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use McpForWoo\Core\McpStreamableTransport;
use McpForWoo\Core\WpMcp;
use McpForWoo\Core\McpStdioTransport;
use McpForWoo\Admin\Settings;
use McpForWoo\CLI\ValidateToolsCommand;

define( 'MCPFOWO_VERSION', '1.3.0' );
define( 'MCPFOWO_PATH', plugin_dir_path( __FILE__ ) );
define( 'MCPFOWO_URL', plugin_dir_url( __FILE__ ) );
define( 'MCPFOWO_PLUGIN_FILE', __FILE__ );

// Check if Composer autoloader exists.
if ( ! file_exists( MCPFOWO_PATH . 'vendor/autoload.php' ) ) {
	wp_die(
		sprintf(
			'Please run <code>composer install</code> in the plugin directory: <code>%s</code>',
			esc_html( MCPFOWO_PATH )
		)
	);
}

require_once MCPFOWO_PATH . 'vendor/autoload.php';

/**
 * Get the WordPress MCP instance.
 *
 * @return WpMcp
 */
function mcpfowo_instance() {
	return WpMcp::instance();
}

/**
 * Initialize the plugin.
 */
function mcpfowo_init_plugin() {
	$mcp = mcpfowo_instance();

	// Initialize the STDIO transport.
	new McpStdioTransport( $mcp );

	// Initialize the Streamable transport.
	new McpStreamableTransport( $mcp );

	// Initialize the settings page.
	new Settings();

	// Text domain is automatically loaded by WordPress for WordPress.org hosted plugins
}

/**
 * Register WP-CLI commands
 */
function mcpfowo_register_cli_commands() {
	if ( ! class_exists( 'WP_CLI' ) ) {
		return;
	}

	WP_CLI::add_command( 'mcp-for-woocommerce validate-tools', ValidateToolsCommand::class );
}

/**
 * Plugin activation hook.
 */
function mcpfowo_activate() {
	mcpfowo_maybe_upgrade();
}

/**
 * Delete the static OAuth discovery file written by versions before 1.2.4.
 */
function mcpfowo_remove_legacy_discovery_file() {
	$legacy_discovery_file = ABSPATH . '.well-known/oauth-authorization-server';
	if ( file_exists( $legacy_discovery_file ) ) {
		wp_delete_file( $legacy_discovery_file );
	}
}

/**
 * Remove data left behind by versions before 1.3.0.
 *
 * Earlier versions issued JWT access tokens and, when authentication was turned
 * off, wrote a generated mcp-proxy.js into the uploads directory. Both features
 * were removed; this deletes what they stored so nothing stale stays on the site.
 * It runs once per site, after the stored version falls behind the plugin version.
 */
function mcpfowo_maybe_upgrade() {
	if ( version_compare( (string) get_option( 'mcpfowo_db_version', '0' ), '1.3.0', '>=' ) ) {
		return;
	}

	foreach ( array( 'mcpfowo_jwt_required', 'mcpfowo_jwt_secret_key', 'mcpfowo_jwt_token_registry', 'mcpfowo_oauth_auth_codes', 'mcpfowo_oauth_clients' ) as $legacy_option ) {
		delete_option( $legacy_option );
	}

	$upload_dir = wp_upload_dir( null, false );
	if ( ! empty( $upload_dir['basedir'] ) ) {
		$legacy_proxy_file = trailingslashit( $upload_dir['basedir'] ) . 'mcp-for-woocommerce/mcp-proxy.js';
		if ( file_exists( $legacy_proxy_file ) ) {
			wp_delete_file( $legacy_proxy_file );
		}
	}

	mcpfowo_remove_legacy_discovery_file();

	update_option( 'mcpfowo_db_version', MCPFOWO_VERSION );
}

/**
 * Plugin deactivation hook.
 */
function mcpfowo_deactivate() {
	mcpfowo_remove_legacy_discovery_file();
	flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'mcpfowo_activate' );
register_deactivation_hook( __FILE__, 'mcpfowo_deactivate' );

// Clean up data left by earlier versions once, after an update.
add_action( 'admin_init', 'mcpfowo_maybe_upgrade' );

// Initialize the plugin on plugins_loaded to ensure all dependencies are available.
add_action( 'plugins_loaded', 'mcpfowo_init_plugin' );

// Register CLI commands
add_action( 'cli_init', 'mcpfowo_register_cli_commands' );

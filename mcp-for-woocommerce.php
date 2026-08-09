<?php
/**
 * Plugin name:       MCP for WooCommerce
 * Description:       Community-developed AI integration plugin that connects WooCommerce & WordPress with Model Context Protocol (MCP). Not affiliated with Automattic. Provides comprehensive AI-accessible interfaces to WooCommerce products, orders, categories, shipping, payments, and WordPress posts/pages through standardized tools, resources, and prompts. Enables AI assistants to seamlessly interact with your e-commerce data and content. Acts as a WooCommerce MCP Server for MCP clients; pair with Webtalkbot to add a WooCommerce AI Chatbot/Agent to your site.
 * Version:           1.2.4
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
use McpForWoo\Auth\JwtAuth;
use McpForWoo\CLI\ValidateToolsCommand;

define( 'MCPFOWO_VERSION', '1.2.4' );
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
function WPMCP() { // phpcs:ignore
	return WpMcp::instance();
}

/**
 * Initialize the plugin.
 */
function mcpfowo_init_plugin() {
	$mcp = WPMCP();

	// Initialize the STDIO transport.
	new McpStdioTransport( $mcp );

	// Initialize the Streamable transport.
	new McpStreamableTransport( $mcp );

	// Initialize the settings page.
	new Settings();

	// Initialize the JWT authentication.
	new JwtAuth();

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
	// The OAuth discovery document at /.well-known/oauth-authorization-server is
	// served dynamically by JwtAuth::handle_oauth_discovery(). Earlier versions
	// wrote a static copy into the web root; that is unnecessary, goes stale when
	// the site URL changes, and writing outside wp-content is bad practice.
	// Remove any stale copy left by an earlier version, which would otherwise be
	// served by the web server in preference to the dynamic handler.
	mcpfowo_remove_legacy_discovery_file();

	flush_rewrite_rules();
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
 * Plugin deactivation hook.
 */
function mcpfowo_deactivate() {
	mcpfowo_remove_legacy_discovery_file();
	flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'mcpfowo_activate' );
register_deactivation_hook( __FILE__, 'mcpfowo_deactivate' );

// Initialize the plugin on plugins_loaded to ensure all dependencies are available.
add_action( 'plugins_loaded', 'mcpfowo_init_plugin' );

// Register CLI commands
add_action( 'cli_init', 'mcpfowo_register_cli_commands' );

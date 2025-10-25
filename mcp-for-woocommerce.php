<?php
/**
 * Plugin name:       MCP for WooCommerce
 * Description:       Community-developed AI integration plugin that connects WooCommerce & WordPress with Model Context Protocol (MCP). Not affiliated with Automattic. Provides comprehensive AI-accessible interfaces to WooCommerce products, orders, categories, shipping, payments, and WordPress posts/pages through standardized tools, resources, and prompts. Enables AI assistants to seamlessly interact with your e-commerce data and content. Acts as a WooCommerce MCP Server for MCP clients; pair with Webtalkbot to add a WooCommerce AI Chatbot/Agent to your site.
 * Version:           1.1.9
 * Requires at least: 6.4
 * Tested up to:      6.8
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

define( 'MCPFOWO_VERSION', '1.1.9' );
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
function init_mcpfowo() {
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
function register_mcpfowo_cli_commands() {
	if ( ! class_exists( 'WP_CLI' ) ) {
		return;
	}

	WP_CLI::add_command( 'mcp-for-woocommerce validate-tools', ValidateToolsCommand::class );
}

// Initialize the plugin on plugins_loaded to ensure all dependencies are available.
add_action( 'plugins_loaded', 'init_mcpfowo' );

// Register CLI commands
add_action( 'cli_init', 'register_mcpfowo_cli_commands' );

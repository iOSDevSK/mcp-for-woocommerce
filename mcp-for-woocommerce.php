<?php
/**
 * Plugin name:       MCP for WooCommerce
 * Description:       Community-developed AI integration plugin that connects WooCommerce & WordPress with Model Context Protocol (MCP). Not affiliated with Automattic. Provides comprehensive AI-accessible interfaces to WooCommerce products, orders, categories, shipping, payments, and WordPress posts/pages through standardized tools, resources, and prompts. Enables AI assistants to seamlessly interact with your e-commerce data and content. Acts as a WooCommerce MCP Server for MCP clients; pair with Webtalkbot to add a WooCommerce AI Chatbot/Agent to your site.
 * Version:           1.2.1
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

define( 'MCPFOWO_VERSION', '1.2.1' );
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

/**
 * Plugin activation hook.
 */
function mcpfowo_activate() {
	// Create .well-known directory if it doesn't exist
	$well_known_dir = ABSPATH . '.well-known';
	if ( ! file_exists( $well_known_dir ) ) {
		wp_mkdir_p( $well_known_dir );
	}

	// Create OAuth discovery file
	$oauth_discovery_file = $well_known_dir . '/oauth-authorization-server';
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

	// Write the file with proper JSON formatting
	file_put_contents(
		$oauth_discovery_file,
		wp_json_encode( $discovery_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
	);

	// Flush rewrite rules
	flush_rewrite_rules();
}

/**
 * Plugin deactivation hook.
 */
function mcpfowo_deactivate() {
	// Optionally remove the OAuth discovery file
	$oauth_discovery_file = ABSPATH . '.well-known/oauth-authorization-server';
	if ( file_exists( $oauth_discovery_file ) ) {
		unlink( $oauth_discovery_file );
	}

	// Flush rewrite rules
	flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'mcpfowo_activate' );
register_deactivation_hook( __FILE__, 'mcpfowo_deactivate' );

// Initialize the plugin on plugins_loaded to ensure all dependencies are available.
add_action( 'plugins_loaded', 'init_mcpfowo' );

// Register CLI commands
add_action( 'cli_init', 'register_mcpfowo_cli_commands' );

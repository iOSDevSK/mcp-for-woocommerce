<?php
/**
 * Plugin name:       Woo MCP
 * Description:       Advanced AI integration plugin that connects WooCommerce & WordPress with Model Context Protocol (MCP). Provides comprehensive AI-accessible interfaces to WooCommerce products, orders, categories, shipping, payments, and WordPress posts/pages through standardized tools, resources, and prompts. Enables AI assistants to seamlessly interact with your e-commerce data and content.
 * Version:           1.1.5
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Automattic AI, Ovidiu Galatan, Filip Dvoran, Claude
 * Author URI:        https://automattic.com
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       wordpress-mcp
 * Domain Path:       /languages
 *
 * @package WordPress MCP
 */

declare(strict_types=1);

use Automattic\WordpressMcp\Core\McpStreamableTransport;
use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Core\McpStdioTransport;
use Automattic\WordpressMcp\Admin\Settings;
use Automattic\WordpressMcp\Auth\JwtAuth;
use Automattic\WordpressMcp\CLI\ValidateToolsCommand;

define( 'WORDPRESS_MCP_VERSION', '1.1.5' );
define( 'WORDPRESS_MCP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WORDPRESS_MCP_URL', plugin_dir_url( __FILE__ ) );

// Check if Composer autoloader exists.
if ( ! file_exists( WORDPRESS_MCP_PATH . 'vendor/autoload.php' ) ) {
	wp_die(
		sprintf(
			'Please run <code>composer install</code> in the plugin directory: <code>%s</code>',
			esc_html( WORDPRESS_MCP_PATH )
		)
	);
}

require_once WORDPRESS_MCP_PATH . 'vendor/autoload.php';

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
function init_wordpress_mcp() {
	$mcp = WPMCP();

	// Initialize the STDIO transport.
	new McpStdioTransport( $mcp );

	// Initialize the Streamable transport.
	new McpStreamableTransport( $mcp );

	// Initialize the settings page.
	new Settings();

	// Initialize the JWT authentication.
	new JwtAuth();
}

/**
 * Register WP-CLI commands
 */
function register_wordpress_mcp_cli_commands() {
	if ( ! class_exists( 'WP_CLI' ) ) {
		return;
	}

	WP_CLI::add_command( 'woo-mcp validate-tools', ValidateToolsCommand::class );
}

// Initialize the plugin on plugins_loaded to ensure all dependencies are available.
add_action( 'plugins_loaded', 'init_wordpress_mcp' );

// Register CLI commands
add_action( 'cli_init', 'register_wordpress_mcp_cli_commands' );

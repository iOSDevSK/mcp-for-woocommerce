<?php
/**
 * Plugin name:       Woo - MCP
 * Description:       A specialized Model Context Protocol (MCP) plugin for WooCommerce that provides AI-accessible interfaces to WooCommerce data and functionality. Enables AI assistants to interact with products, orders, customers, shipping, and all WooCommerce features through standardized tools, resources, and prompts.
 * Version:           0.2.3
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Automattic AI, Ovidiu Galatan <ovidiu.galatan@a8c.com>, Claude AI Assistant, Belnem s.r.o.
 * Author URI:        https://automattic.com
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       wordpress-mcp
 * Domain Path:       /languages
 *
 * @package Woo - MCP
 */

declare(strict_types=1);

use Automattic\WordpressMcp\Core\McpStreamableTransport;
use Automattic\WordpressMcp\Core\WpMcp;
use Automattic\WordpressMcp\Core\McpStdioTransport;
use Automattic\WordpressMcp\Admin\Settings;
use Automattic\WordpressMcp\Auth\JwtAuth;

define( 'WORDPRESS_MCP_VERSION', '0.2.3' );
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

// Initialize the plugin on plugins_loaded to ensure all dependencies are available.
add_action( 'plugins_loaded', 'init_wordpress_mcp' );

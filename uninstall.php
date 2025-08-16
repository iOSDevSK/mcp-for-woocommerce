<?php
/**
 * Uninstall script for MCP for WooCommerce plugin
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It cleans up all plugin data including options, transients, and any other data.
 *
 * @package WordPress MCP
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Clean up plugin options
 */
function mcp_for_woocommerce_cleanup_options() {
    // Remove plugin options
    $options_to_delete = [
        'wordpress_mcp_settings',
        'mcp_for_woocommerce_jwt_secret',
        'mcp_for_woocommerce_jwt_tokens',
        'mcp_for_woocommerce_auth_settings',
        'mcp_for_woocommerce_transport_settings',
        'mcp_for_woocommerce_debug_mode',
        'mcp_for_woocommerce_allowed_origins',
        'mcp_for_woocommerce_rate_limit_settings',
    ];

    foreach ( $options_to_delete as $option ) {
        delete_option( $option );
        delete_site_option( $option ); // For multisite
    }
}

/**
 * Clean up transients
 */
function mcp_for_woocommerce_cleanup_transients() {
    global $wpdb;

    // Delete all transients that start with mcp_for_woocommerce_
    $wpdb->query( 
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_mcp_for_woocommerce_%',
            '_transient_timeout_mcp_for_woocommerce_%'
        )
    );

    // For multisite
    if ( is_multisite() ) {
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                '_site_transient_mcp_for_woocommerce_%',
                '_site_transient_timeout_mcp_for_woocommerce_%'
            )
        );
    }
}

/**
 * Clean up user meta
 */
function mcp_for_woocommerce_cleanup_user_meta() {
    global $wpdb;

    // Delete user meta related to the plugin
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            'mcp_for_woocommerce_%'
        )
    );
}

/**
 * Clean up any custom tables (if any were created)
 */
function mcp_for_woocommerce_cleanup_custom_tables() {
    // This plugin doesn't create custom tables, but if it did in the future,
    // we would drop them here
    // global $wpdb;
    // $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mcp_for_woocommerce_logs" );
}

/**
 * Clear any cached data
 */
function mcp_for_woocommerce_clear_caches() {
    // Clear object cache
    wp_cache_flush();
    
    // Clear any plugin-specific cache directories
    $cache_dir = WP_CONTENT_DIR . '/cache/mcp-for-woocommerce/';
    if ( is_dir( $cache_dir ) ) {
        mcp_for_woocommerce_recursive_rmdir( $cache_dir );
    }
}

/**
 * Recursively remove directory
 *
 * @param string $dir Directory path
 */
function mcp_for_woocommerce_recursive_rmdir( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return;
    }

    $files = array_diff( scandir( $dir ), [ '.', '..' ] );
    
    foreach ( $files as $file ) {
        $path = $dir . '/' . $file;
        if ( is_dir( $path ) ) {
            mcp_for_woocommerce_recursive_rmdir( $path );
        } else {
            wp_delete_file( $path );
        }
    }
    
    // Use WP_Filesystem for directory operations
    global $wp_filesystem;
    if ( empty( $wp_filesystem ) ) {
        require_once ABSPATH . '/wp-admin/includes/file.php';
        WP_Filesystem();
    }
    $wp_filesystem->rmdir( $dir );
}

// Execute cleanup functions
mcp_for_woocommerce_cleanup_options();
mcp_for_woocommerce_cleanup_transients();
mcp_for_woocommerce_cleanup_user_meta();
mcp_for_woocommerce_cleanup_custom_tables();
mcp_for_woocommerce_clear_caches();


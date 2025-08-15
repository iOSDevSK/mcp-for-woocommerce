<?php
/**
 * Uninstall script for Woo MCP plugin
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
function woo_mcp_cleanup_options() {
    // Remove plugin options
    $options_to_delete = [
        'wordpress_mcp_settings',
        'woo_mcp_jwt_secret',
        'woo_mcp_jwt_tokens',
        'woo_mcp_auth_settings',
        'woo_mcp_transport_settings',
        'woo_mcp_debug_mode',
        'woo_mcp_allowed_origins',
        'woo_mcp_rate_limit_settings',
    ];

    foreach ( $options_to_delete as $option ) {
        delete_option( $option );
        delete_site_option( $option ); // For multisite
    }
}

/**
 * Clean up transients
 */
function woo_mcp_cleanup_transients() {
    global $wpdb;

    // Delete all transients that start with woo_mcp_
    $wpdb->query( 
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_woo_mcp_%',
            '_transient_timeout_woo_mcp_%'
        )
    );

    // For multisite
    if ( is_multisite() ) {
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                '_site_transient_woo_mcp_%',
                '_site_transient_timeout_woo_mcp_%'
            )
        );
    }
}

/**
 * Clean up user meta
 */
function woo_mcp_cleanup_user_meta() {
    global $wpdb;

    // Delete user meta related to the plugin
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            'woo_mcp_%'
        )
    );
}

/**
 * Clean up any custom tables (if any were created)
 */
function woo_mcp_cleanup_custom_tables() {
    // This plugin doesn't create custom tables, but if it did in the future,
    // we would drop them here
    // global $wpdb;
    // $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woo_mcp_logs" );
}

/**
 * Clear any cached data
 */
function woo_mcp_clear_caches() {
    // Clear object cache
    wp_cache_flush();
    
    // Clear any plugin-specific cache directories
    $cache_dir = WP_CONTENT_DIR . '/cache/woo-mcp/';
    if ( is_dir( $cache_dir ) ) {
        woo_mcp_recursive_rmdir( $cache_dir );
    }
}

/**
 * Recursively remove directory
 *
 * @param string $dir Directory path
 */
function woo_mcp_recursive_rmdir( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return;
    }

    $files = array_diff( scandir( $dir ), [ '.', '..' ] );
    
    foreach ( $files as $file ) {
        $path = $dir . '/' . $file;
        if ( is_dir( $path ) ) {
            woo_mcp_recursive_rmdir( $path );
        } else {
            unlink( $path );
        }
    }
    
    rmdir( $dir );
}

// Execute cleanup functions
woo_mcp_cleanup_options();
woo_mcp_cleanup_transients();
woo_mcp_cleanup_user_meta();
woo_mcp_cleanup_custom_tables();
woo_mcp_clear_caches();

// Log the uninstall for debugging purposes (if WordPress debug logging is enabled)
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
    error_log( 'Woo MCP plugin has been uninstalled and all data cleaned up.' );
}
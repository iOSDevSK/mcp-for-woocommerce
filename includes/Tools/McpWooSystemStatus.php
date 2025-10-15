<?php
declare(strict_types=1);


namespace McpForWoo\Tools;

use McpForWoo\Core\RegisterMcpTool;

/**
 * Class McpWooSystemStatus
 * 
 * Provides WooCommerce system status information readonly tools.
 * Only registers tools if WooCommerce is active.
 */
class McpWooSystemStatus {

    public function __construct() {
        add_action('mcpfowo_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_system_status',
            'description' => 'Get WooCommerce system status information (versions, settings, environment)',
            'type' => 'read',
            'callback' => [$this, 'get_system_status'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object)[]
            ],
            'annotations' => [
                'title' => 'Get System Status',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_system_tools',
            'description' => 'Get available WooCommerce system tools and utilities',
            'type' => 'read',
            'callback' => [$this, 'get_system_tools'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object)[]
            ],
            'annotations' => [
                'title' => 'Get System Tools',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    /**
     * Get system status
     */
    public function get_system_status($params): array {
        // Get WooCommerce system status
        $status = [];
        
        // Environment
        $status['environment'] = [
            'home_url' => get_option('home'),
            'site_url' => get_option('siteurl'),
            'version' => WC()->version,
            'log_directory' => WC_LOG_DIR,
            'log_directory_writable' => wp_is_writable(WC_LOG_DIR),
            'wp_version' => get_bloginfo('version'),
            'wp_multisite' => is_multisite(),
            'wp_memory_limit' => WP_MEMORY_LIMIT,
            'wp_debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_cron' => !(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON),
            'language' => get_locale(),
            'external_object_cache' => wp_using_ext_object_cache()
        ];
        
        // Database
        $status['database'] = [
            'wc_database_version' => get_option('woocommerce_db_version'),
            'database_prefix' => $GLOBALS['wpdb']->prefix,
            'maxmind_geoip_database' => WC_Geolocation::get_local_database_path()
        ];
        
        // Active plugins
        $active_plugins = get_option('active_plugins', []);
        $status['active_plugins'] = [];
        
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $status['active_plugins'][] = [
                'plugin' => $plugin,
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'author' => $plugin_data['Author']
            ];
        }
        
        // Theme
        $theme = wp_get_theme();
        $status['theme'] = [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author'),
            'child_theme' => is_child_theme()
        ];
        
        return ['system_status' => $status];
    }

    /**
     * Get system tools
     */
    public function get_system_tools($params): array {
        $tools = [
            [
                'id' => 'clear_transients',
                'name' => 'Clear transients',
                'action' => 'Clear all WooCommerce transients',
                'description' => 'This tool will clear the product/shop transients cache.'
            ],
            [
                'id' => 'clear_expired_transients',
                'name' => 'Clear expired transients',
                'action' => 'Clear expired transients',
                'description' => 'This tool will clear ALL expired transients from WordPress.'
            ],
            [
                'id' => 'delete_orphaned_variations',
                'name' => 'Delete orphaned variations',
                'action' => 'Delete orphaned variations',
                'description' => 'This tool will delete all variations which have no parent.'
            ],
            [
                'id' => 'recount_terms',
                'name' => 'Term counts',
                'action' => 'Recount terms',
                'description' => 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.'
            ],
            [
                'id' => 'reset_roles',
                'name' => 'Capabilities',
                'action' => 'Reset capabilities',
                'description' => 'This tool will reset the admin, customer and shop_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.'
            ],
            [
                'id' => 'clear_sessions',
                'name' => 'Clear customer sessions',
                'action' => 'Clear all sessions',
                'description' => 'This tool will delete all customer session data from the database.'
            ]
        ];
        
        return ['tools' => $tools, 'total' => count($tools)];
    }
}

<?php
/**
 * Debug script - manually test MCP admin page registration
 */

// WordPress loading
require_once('/var/www/html/wp-config.php');
require_once('/var/www/html/wp-load.php');
define('WP_ADMIN', true);

// Set up admin context
$_GET['page'] = 'wordpress-mcp-settings';
set_current_screen('settings_page_wordpress-mcp-settings');

// Load our plugin
require_once('/var/www/html/wp-content/plugins/woo-mcp/wordpress-mcp.php');

echo "=== MCP DEBUG TEST ===" . PHP_EOL;

// Test if main plugin class exists
if (class_exists('Automattic\WordpressMcp\Core\WpMcp')) {
    echo "✅ WpMcp class exists" . PHP_EOL;
    
    // Instantiate plugin
    $wp_mcp = new Automattic\WordpressMcp\Core\WpMcp();
    echo "✅ Plugin instantiated" . PHP_EOL;
    
    // Test Settings class
    if (class_exists('Automattic\WordpressMcp\Admin\Settings')) {
        echo "✅ Settings class exists" . PHP_EOL;
        
        $settings = new Automattic\WordpressMcp\Admin\Settings();
        echo "✅ Settings instantiated" . PHP_EOL;
        
        // Test admin_enqueue_scripts method
        if (method_exists($settings, 'admin_enqueue_scripts')) {
            echo "✅ admin_enqueue_scripts method exists" . PHP_EOL;
            
            // Test if we're on the right page
            $screen = get_current_screen();
            echo "Current screen: " . ($screen ? $screen->id : 'none') . PHP_EOL;
            
            // Test JWT settings
            $jwt_required = get_option('wordpress_mcp_jwt_required', true);
            echo "JWT required: " . var_export($jwt_required, true) . PHP_EOL;
            
            // Test proxy generation
            if (class_exists('Automattic\WordpressMcp\Core\McpProxyGenerator')) {
                echo "✅ McpProxyGenerator class exists" . PHP_EOL;
                
                $should_generate = Automattic\WordpressMcp\Core\McpProxyGenerator::should_generate_proxy();
                echo "Should generate proxy: " . ($should_generate ? 'YES' : 'NO') . PHP_EOL;
                
                if ($should_generate) {
                    $instructions = Automattic\WordpressMcp\Core\McpProxyGenerator::get_claude_setup_instructions();
                    echo "Instructions generated: " . (empty($instructions) ? 'NO' : 'YES') . PHP_EOL;
                }
            }
        }
    }
} else {
    echo "❌ WpMcp class NOT found" . PHP_EOL;
}

echo "=== END DEBUG ===" . PHP_EOL;
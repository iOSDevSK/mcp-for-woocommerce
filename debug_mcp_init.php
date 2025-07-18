<?php
/**
 * Debug script to check if wordpress_mcp_init is being triggered
 * and if WooCommerce is detected.
 */

// Set up WordPress environment
define('WP_USE_THEMES', false);
require_once('wp-load.php');

// Add debug hooks
add_action('wordpress_mcp_init', function() {
    echo "wordpress_mcp_init action triggered\n";
    
    // Check if WooCommerce is active
    if (class_exists('WooCommerce')) {
        echo "WooCommerce is detected\n";
    } else {
        echo "WooCommerce is NOT detected\n";
    }
    
    // Check MCP settings
    $mcp_settings = get_option('wordpress_mcp_settings', array());
    echo "MCP enabled: " . (isset($mcp_settings['enabled']) && $mcp_settings['enabled'] ? 'Yes' : 'No') . "\n";
    echo "MCP settings: " . json_encode($mcp_settings) . "\n";
});

// Add a high priority hook to see if the action is being triggered at all
add_action('wordpress_mcp_init', function() {
    echo "High priority wordpress_mcp_init hook triggered\n";
}, 1);

// Try to get the MCP instance and trigger init
try {
    $mcp = \Automattic\WordpressMcp\Core\WpMcp::instance();
    echo "MCP instance created successfully\n";
    
    // Try to trigger the init manually
    $mcp->wordpress_mcp_init();
    echo "MCP init called manually\n";
    
    // Check if any tools are registered
    $tools = $mcp->get_tools();
    echo "Total tools registered: " . count($tools) . "\n";
    
    // Look for shipping tools specifically
    $shipping_tools = array_filter($tools, function($tool) {
        return strpos($tool['name'], 'wc_') === 0 && strpos($tool['name'], 'shipping') !== false;
    });
    
    echo "Shipping tools found: " . count($shipping_tools) . "\n";
    foreach ($shipping_tools as $tool) {
        echo "  - " . $tool['name'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
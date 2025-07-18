<?php
/**
 * Debug script to test tool registration specifically
 */

// Set up WordPress environment
define('WP_USE_THEMES', false);
require_once('wp-load.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if WordPress MCP is loaded
if (!class_exists('Automattic\WordpressMcp\Core\WpMcp')) {
    echo "WordPress MCP not loaded\n";
    exit;
}

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    echo "WooCommerce is not active\n";
    exit;
}

echo "WooCommerce is active\n";

// Check MCP settings
$mcp_settings = get_option('wordpress_mcp_settings', array());
echo "MCP settings: " . json_encode($mcp_settings) . "\n";

// Try to manually register the tools to see if there are any errors
try {
    // Hook into the registration process
    add_action('wordpress_mcp_init', function() {
        echo "wordpress_mcp_init action triggered\n";
        
        // Try to create the shipping tools manually
        try {
            $shipping_tool = new \Automattic\WordpressMcp\Core\RegisterMcpTool([
                'name' => 'debug_wc_test_tool',
                'description' => 'Test tool for debugging',
                'type' => 'read',
                'callback' => function($params) { return ['test' => 'success']; },
                'permission_callback' => '__return_true',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => []
                ]
            ]);
            echo "Successfully created debug test tool\n";
        } catch (Exception $e) {
            echo "Error creating debug test tool: " . $e->getMessage() . "\n";
        }
        
        // Try to register the actual shipping tools
        try {
            $shipping_class = new \Automattic\WordpressMcp\Tools\McpWooShipping();
            echo "Successfully created McpWooShipping class\n";
        } catch (Exception $e) {
            echo "Error creating McpWooShipping class: " . $e->getMessage() . "\n";
        }
    });
    
    // Get MCP instance and trigger initialization
    $mcp = \Automattic\WordpressMcp\Core\WpMcp::instance();
    echo "MCP instance created\n";
    
    // Trigger the init
    $mcp->wordpress_mcp_init();
    echo "MCP init triggered\n";
    
    // Check registered tools
    $tools = $mcp->get_tools();
    echo "Total tools registered: " . count($tools) . "\n";
    
    // List all tools
    foreach ($tools as $tool) {
        echo "Tool: " . $tool['name'] . "\n";
    }
    
    // Check specifically for shipping tools
    $shipping_tools = array_filter($tools, function($tool) {
        return strpos($tool['name'], 'shipping') !== false;
    });
    
    echo "Shipping tools found: " . count($shipping_tools) . "\n";
    foreach ($shipping_tools as $tool) {
        echo "  - " . $tool['name'] . " (" . $tool['description'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
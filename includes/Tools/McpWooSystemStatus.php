<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooSystemStatus
 * 
 * Provides WooCommerce system status information readonly tools.
 * Only registers tools if WooCommerce is active.
 */
class McpWooSystemStatus {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
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
            'rest_alias' => [
                'route' => '/wc/v3/system_status',
                'method' => 'GET'
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
            'rest_alias' => [
                'route' => '/wc/v3/system_status/tools',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get System Tools',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }
}
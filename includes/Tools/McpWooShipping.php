<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooShipping
 * 
 * Provides WooCommerce shipping information readonly tools.
 * Only registers tools if WooCommerce is active.
 */
class McpWooShipping {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_shipping_zones',
            'description' => 'Get all WooCommerce shipping zones and their coverage areas',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/shipping/zones',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Shipping Zones',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_shipping_zone',
            'description' => 'Get details about a specific WooCommerce shipping zone',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/shipping/zones/(?P<id>[\d]+)',
                'method' => 'GET',
                'inputSchemaReplacements' => [
                    'required' => ['id']
                ]
            ],
            'annotations' => [
                'title' => 'Get Shipping Zone',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_shipping_methods',
            'description' => 'Get all shipping methods available for a specific shipping zone',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/shipping/zones/(?P<zone_id>[\d]+)/methods',
                'method' => 'GET',
                'inputSchemaReplacements' => [
                    'required' => ['zone_id']
                ]
            ],
            'annotations' => [
                'title' => 'Get Shipping Methods',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_shipping_locations',
            'description' => 'Get all locations (countries/states) covered by a specific shipping zone',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/shipping/zones/(?P<zone_id>[\d]+)/locations',
                'method' => 'GET',
                'inputSchemaReplacements' => [
                    'required' => ['zone_id']
                ]
            ],
            'annotations' => [
                'title' => 'Get Shipping Locations',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }
}
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
            'callback' => [$this, 'get_shipping_zones'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object)[]
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
            'callback' => [$this, 'get_shipping_zone'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Shipping Zone ID',
                        'minimum' => 1
                    ]
                ],
                'required' => ['id']
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
            'callback' => [$this, 'get_shipping_methods'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'zone_id' => [
                        'type' => 'integer',
                        'description' => 'Shipping Zone ID',
                        'minimum' => 1
                    ]
                ],
                'required' => ['zone_id']
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
            'callback' => [$this, 'get_shipping_locations'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'zone_id' => [
                        'type' => 'integer',
                        'description' => 'Shipping Zone ID',
                        'minimum' => 1
                    ]
                ],
                'required' => ['zone_id']
            ],
            'annotations' => [
                'title' => 'Get Shipping Locations',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    /**
     * Get all shipping zones
     */
    public function get_shipping_zones($params): array {
        $zones = WC_Shipping_Zones::get_zones();
        $results = [];
        
        foreach ($zones as $zone_id => $zone_data) {
            $zone = new \WC_Shipping_Zone($zone_id);
            $results[] = [
                'id' => $zone_id,
                'name' => $zone_data['zone_name'],
                'order' => $zone_data['zone_order'],
                'locations' => $zone_data['zone_locations']
            ];
        }
        
        // Add default zone (rest of the world)
        $default_zone = new \WC_Shipping_Zone(0);
        $results[] = [
            'id' => 0,
            'name' => $default_zone->get_zone_name(),
            'order' => 0,
            'locations' => []
        ];
        
        return ['zones' => $results, 'total' => count($results)];
    }

    /**
     * Get single shipping zone
     */
    public function get_shipping_zone($params): array {
        $zone_id = $params['id'];
        $zone = new \WC_Shipping_Zone($zone_id);
        
        if (!$zone->get_id() && $zone_id != 0) {
            return ['error' => 'Shipping zone not found'];
        }
        
        return [
            'zone' => [
                'id' => $zone->get_id(),
                'name' => $zone->get_zone_name(),
                'order' => $zone->get_zone_order(),
                'locations' => $zone->get_zone_locations()
            ]
        ];
    }

    /**
     * Get shipping methods for zone
     */
    public function get_shipping_methods($params): array {
        $zone_id = $params['zone_id'];
        $zone = new \WC_Shipping_Zone($zone_id);
        
        if (!$zone->get_id() && $zone_id != 0) {
            return ['error' => 'Shipping zone not found'];
        }
        
        $methods = $zone->get_shipping_methods(true);
        $results = [];
        
        foreach ($methods as $method) {
            $results[] = [
                'id' => $method->get_instance_id(),
                'method_id' => $method->id,
                'title' => $method->get_title(),
                'enabled' => $method->is_enabled(),
                'settings' => $method->get_instance_form_fields()
            ];
        }
        
        return ['methods' => $results, 'total' => count($results)];
    }

    /**
     * Get shipping locations for zone
     */
    public function get_shipping_locations($params): array {
        $zone_id = $params['zone_id'];
        $zone = new \WC_Shipping_Zone($zone_id);
        
        if (!$zone->get_id() && $zone_id != 0) {
            return ['error' => 'Shipping zone not found'];
        }
        
        $locations = $zone->get_zone_locations();
        $results = [];
        
        foreach ($locations as $location) {
            $results[] = [
                'code' => $location->code,
                'type' => $location->type
            ];
        }
        
        return ['locations' => $results, 'total' => count($results)];
    }
}
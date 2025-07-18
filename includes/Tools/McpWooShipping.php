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

    private const WC_API_NAMESPACE = '/wc/v3';

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Skip route validation during early initialization
        // WooCommerce routes may not be registered yet at this point

        new RegisterMcpTool([
            'name' => 'wc_get_shipping_zones',
            'description' => 'Get all WooCommerce shipping zones and their coverage areas',
            'type' => 'read',
            'rest_alias' => [
                'route' => self::WC_API_NAMESPACE . '/shipping/zones',
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
                'route' => self::WC_API_NAMESPACE . '/shipping/zones/(?P<id>[1-9]\d{0,9})',
                'method' => 'GET',
                'inputSchemaReplacements' => [
                    'required' => ['id'],
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 9999999999,
                            'description' => 'Shipping zone ID (positive integer)'
                        ]
                    ]
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
                'route' => self::WC_API_NAMESPACE . '/shipping/zones/(?P<zone_id>[1-9]\d{0,9})/methods',
                'method' => 'GET',
                'inputSchemaReplacements' => [
                    'required' => ['zone_id'],
                    'properties' => [
                        'zone_id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 9999999999,
                            'description' => 'Shipping zone ID (positive integer)'
                        ]
                    ]
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
                'route' => self::WC_API_NAMESPACE . '/shipping/zones/(?P<zone_id>[1-9]\d{0,9})/locations',
                'method' => 'GET',
                'inputSchemaReplacements' => [
                    'required' => ['zone_id'],
                    'properties' => [
                        'zone_id' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 9999999999,
                            'description' => 'Shipping zone ID (positive integer)'
                        ]
                    ]
                ]
            ],
            'annotations' => [
                'title' => 'Get Shipping Locations',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    /**
     * Validate that required WooCommerce REST API routes exist.
     * 
     * @return bool
     */
    private function validate_woocommerce_routes(): bool {
        if (!function_exists('rest_get_server')) {
            return false;
        }

        $routes = rest_get_server()->get_routes();
        $required_routes = [
            '/wc/v3/shipping/zones',
            '/wc/v3/shipping/zones/(?P<id>[\d]+)',
            '/wc/v3/shipping/zones/(?P<zone_id>[\d]+)/methods',
            '/wc/v3/shipping/zones/(?P<zone_id>[\d]+)/locations'
        ];

        foreach ($required_routes as $route) {
            if (!isset($routes[$route])) {
                error_log("WooCommerce MCP: Required route not found: $route");
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that a shipping zone exists.
     * 
     * @param int $zone_id The zone ID to validate
     * @return bool
     */
    public static function validate_zone_exists(int $zone_id): bool {
        if (!class_exists('WC_Shipping_Zones')) {
            return false;
        }

        // Get the zone
        $zone = \WC_Shipping_Zones::get_zone($zone_id);
        
        // Check if zone exists (get_zone returns a valid zone object or null)
        return $zone && $zone->get_id() === $zone_id;
    }
}
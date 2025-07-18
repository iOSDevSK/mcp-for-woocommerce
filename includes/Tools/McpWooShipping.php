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
            McpErrorHandler::log_error('WooCommerce not detected. McpWooShipping tools will not be registered.');
            return;
        }
        
        McpErrorHandler::log_error('WooCommerce detected. Registering McpWooShipping tools.');

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
                'route' => self::WC_API_NAMESPACE . '/shipping/zones/(?P<id>[0-9]\d{0,9})',
                'method' => 'GET',
                'inputSchemaReplacements' => [
                    'required' => ['id'],
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'maximum' => 9999999999,
                            'description' => 'Shipping zone ID (non-negative integer, 0 for default zone)'
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
            'callback' => [$this, 'get_shipping_methods_safe'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'zone_id' => [
                        'type' => 'integer',
                        'minimum' => 0,
                        'maximum' => 9999999999,
                        'description' => 'Shipping zone ID (non-negative integer, 0 for default zone)'
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
            'rest_alias' => [
                'route' => self::WC_API_NAMESPACE . '/shipping/zones/(?P<zone_id>[0-9]\d{0,9})/locations',
                'method' => 'GET',
                'inputSchemaReplacements' => [
                    'required' => ['zone_id'],
                    'properties' => [
                        'zone_id' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'maximum' => 9999999999,
                            'description' => 'Shipping zone ID (non-negative integer, 0 for default zone)'
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

        // Add a tool to get all shipping methods for all zones
        new RegisterMcpTool([
            'name' => 'wc_get_all_shipping_methods',
            'description' => 'Get all shipping methods available across all shipping zones',
            'type' => 'read',
            'callback' => [$this, 'get_all_shipping_methods'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [],
                'required' => []
            ],
            'annotations' => [
                'title' => 'Get All Shipping Methods',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        // Add a tool to check shipping availability for a specific country
        new RegisterMcpTool([
            'name' => 'wc_check_shipping_to_country',
            'description' => 'Check if shipping is available to a specific country and get shipping options',
            'type' => 'read',
            'callback' => [$this, 'check_shipping_to_country'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'country' => [
                        'type' => 'string',
                        'description' => 'Country name or code (e.g., "Australia", "Slovakia", "AU", "SK")'
                    ]
                ],
                'required' => ['country']
            ],
            'annotations' => [
                'title' => 'Check Shipping to Country',
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
     * Get all shipping methods for all zones.
     * 
     * @return array
     */
    public function get_all_shipping_methods(): array {
        if (!class_exists('WC_Shipping_Zones')) {
            return [];
        }

        $all_methods = [];
        $zones = \WC_Shipping_Zones::get_zones();
        
        // Add the default zone (zone 0)
        $zones[] = [
            'id' => 0,
            'zone_name' => 'Locations not covered by your other zones',
            'zone_locations' => []
        ];

        foreach ($zones as $zone) {
            $zone_id = $zone['id'];
            $zone_name = $zone['zone_name'] ?? 'Unknown Zone';
            
            // Get shipping methods for this zone
            $zone_obj = \WC_Shipping_Zones::get_zone($zone_id);
            $shipping_methods = $zone_obj->get_shipping_methods();
            
            // Get zone locations
            $locations = [];
            if (isset($zone['zone_locations'])) {
                foreach ($zone['zone_locations'] as $location) {
                    $locations[] = [
                        'code' => $location->code,
                        'type' => $location->type
                    ];
                }
            }
            
            $zone_methods = [];
            foreach ($shipping_methods as $method) {
                $zone_methods[] = [
                    'id' => $method->id,
                    'method_id' => $method->method_id,
                    'method_title' => $method->method_title,
                    'enabled' => $method->enabled,
                    'settings' => $method->settings
                ];
            }
            
            $all_methods[] = [
                'zone_id' => $zone_id,
                'zone_name' => $zone_name,
                'locations' => $locations,
                'shipping_methods' => $zone_methods
            ];
        }
        
        return $all_methods;
    }

    /**
     * Safely get shipping methods for a zone with proper error handling.
     * 
     * @param array $params Parameters containing zone_id
     * @return array
     */
    public function get_shipping_methods_safe(array $params): array {
        try {
            error_log("McpWooShipping: get_shipping_methods_safe called with params: " . json_encode($params));
            
            if (!class_exists('WC_Shipping_Zones')) {
                error_log("McpWooShipping: WC_Shipping_Zones class not found");
                return [];
            }

            $zone_id = $params['zone_id'] ?? 0;
            if (!is_numeric($zone_id) || $zone_id < 0) {
                error_log("McpWooShipping: Invalid zone_id: " . $zone_id);
                return [];
            }

            $zone_id = (int) $zone_id;
            error_log("McpWooShipping: Processing zone_id: " . $zone_id);

            // Check if zone exists
            if (!self::validate_zone_exists($zone_id)) {
                error_log("McpWooShipping: Zone {$zone_id} does not exist");
                return [];
            }

            // Get the zone and its methods
            $zone = \WC_Shipping_Zones::get_zone($zone_id);
            if (!$zone) {
                error_log("McpWooShipping: Could not get zone object for zone_id: " . $zone_id);
                return [];
            }
            
            $methods = $zone->get_shipping_methods();
            if (!$methods) {
                error_log("McpWooShipping: No methods found for zone_id: " . $zone_id);
                return [];
            }

            $shipping_methods = [];
            foreach ($methods as $method) {
                $shipping_methods[] = [
                    'id' => $method->id,
                    'instance_id' => $method->instance_id,
                    'title' => $method->method_title,
                    'order' => $method->method_order,
                    'enabled' => $method->enabled === 'yes',
                    'method_id' => $method->method_id,
                    'method_title' => $method->method_title,
                    'method_description' => $method->method_description,
                    'settings' => $method->settings
                ];
            }

            error_log("McpWooShipping: Returning " . count($shipping_methods) . " shipping methods for zone_id: " . $zone_id);
            return $shipping_methods;
        } catch (\Exception $e) {
            // Log error but always return empty array to prevent JSON-RPC issues
            error_log("McpWooShipping: Error in get_shipping_methods_safe: " . $e->getMessage());
            error_log("McpWooShipping: Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Check if shipping is available to a specific country.
     * 
     * @param array $params Parameters containing country
     * @return array
     */
    public function check_shipping_to_country(array $params): array {
        if (!class_exists('WC_Shipping_Zones') || !class_exists('WC_Countries')) {
            return [
                'available' => false,
                'message' => 'WooCommerce not available'
            ];
        }

        $country_input = $params['country'] ?? '';
        if (empty($country_input)) {
            return [
                'available' => false,
                'message' => 'Country parameter is required'
            ];
        }

        // Use WooCommerce's built-in country system
        $wc_countries = new \WC_Countries();
        $all_countries = $wc_countries->get_countries();
        
        // Try to find the country code from the input
        $country_code = $this->find_country_code($country_input, $all_countries);
        
        if (!$country_code) {
            return [
                'available' => false,
                'message' => "Country '{$country_input}' not recognized",
                'country_input' => $country_input
            ];
        }

        $country_name = $all_countries[$country_code];
        
        // Get all zones and check if country is covered
        $zones = \WC_Shipping_Zones::get_zones();
        
        $found_zone = null;
        $shipping_methods = [];
        
        // Check each zone for the country
        foreach ($zones as $zone) {
            if (isset($zone['zone_locations'])) {
                foreach ($zone['zone_locations'] as $location) {
                    if ($location->type === 'country' && strtoupper($location->code) === strtoupper($country_code)) {
                        $found_zone = $zone;
                        break 2;
                    }
                }
            }
        }
        
        if (!$found_zone) {
            // Check if default zone (zone 0) has shipping methods
            $default_zone_obj = \WC_Shipping_Zones::get_zone(0);
            $default_methods = $default_zone_obj->get_shipping_methods();
            
            $enabled_methods = [];
            foreach ($default_methods as $method) {
                if ($method->enabled === 'yes') {
                    $enabled_methods[] = [
                        'id' => $method->id,
                        'method_id' => $method->method_id,
                        'title' => $method->method_title,
                        'cost' => $method->get_option('cost', 'N/A'),
                        'enabled' => true
                    ];
                }
            }
            
            if (!empty($enabled_methods)) {
                return [
                    'available' => true,
                    'message' => "Shipping to {$country_name} is available via default zone",
                    'country' => $country_name,
                    'country_code' => $country_code,
                    'zone' => 'Locations not covered by your other zones',
                    'zone_id' => 0,
                    'shipping_methods' => $enabled_methods
                ];
            }
            
            return [
                'available' => false,
                'message' => "Shipping to {$country_name} is not available",
                'country' => $country_name,
                'country_code' => $country_code,
                'reason' => 'No shipping zone configured and no default zone methods available'
            ];
        }
        
        // Get shipping methods for found zone
        $zone_obj = \WC_Shipping_Zones::get_zone($found_zone['id']);
        $methods = $zone_obj->get_shipping_methods();
        
        foreach ($methods as $method) {
            if ($method->enabled === 'yes') {
                $shipping_methods[] = [
                    'id' => $method->id,
                    'method_id' => $method->method_id,
                    'title' => $method->method_title,
                    'cost' => $method->get_option('cost', 'N/A'),
                    'enabled' => true
                ];
            }
        }
        
        if (empty($shipping_methods)) {
            return [
                'available' => false,
                'message' => "Shipping to {$country_name} is not available",
                'country' => $country_name,
                'country_code' => $country_code,
                'zone' => $found_zone['zone_name'],
                'zone_id' => $found_zone['id'],
                'reason' => 'No enabled shipping methods in the assigned zone'
            ];
        }
        
        return [
            'available' => true,
            'message' => "Shipping to {$country_name} is available",
            'country' => $country_name,
            'country_code' => $country_code,
            'zone' => $found_zone['zone_name'],
            'zone_id' => $found_zone['id'],
            'shipping_methods' => $shipping_methods
        ];
    }

    /**
     * Find country code from user input using WooCommerce's country list.
     * 
     * @param string $country_input User input (country name or code)
     * @param array $all_countries WooCommerce countries array
     * @return string|null Country code or null if not found
     */
    private function find_country_code(string $country_input, array $all_countries): ?string {
        $country_input = trim($country_input);
        
        // First, try as country code (exact match)
        $country_code = strtoupper($country_input);
        if (isset($all_countries[$country_code])) {
            return $country_code;
        }
        
        // Then try as country name (case-insensitive)
        $country_input_lower = strtolower($country_input);
        foreach ($all_countries as $code => $name) {
            if (strtolower($name) === $country_input_lower) {
                return $code;
            }
        }
        
        // Finally, try partial match on country name
        foreach ($all_countries as $code => $name) {
            if (stripos($name, $country_input) !== false || stripos($country_input, $name) !== false) {
                return $code;
            }
        }
        
        return null;
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

        try {
            // Get the zone
            $zone = \WC_Shipping_Zones::get_zone($zone_id);
            
            // Check if zone exists (get_zone returns a valid zone object or null)
            return $zone && $zone->get_id() === $zone_id;
        } catch (\Exception $e) {
            // If any exception occurs, consider zone as non-existent
            return false;
        }
    }
}
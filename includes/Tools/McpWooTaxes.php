<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooTaxes
 * 
 * Provides WooCommerce tax information readonly tools.
 * Only registers tools if WooCommerce is active.
 */
class McpWooTaxes {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_tax_classes',
            'description' => 'Get all WooCommerce tax classes (Standard, Reduced Rate, Zero Rate, etc.)',
            'type' => 'read',
            'callback' => [$this, 'get_tax_classes'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object)[]
            ],
            'annotations' => [
                'title' => 'Get Tax Classes',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_tax_rates',
            'description' => 'Get all WooCommerce tax rates with filtering by class, country, state, etc.',
            'type' => 'read',
            'callback' => [$this, 'get_tax_rates'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'class' => [
                        'type' => 'string',
                        'description' => 'Tax class slug to filter by'
                    ],
                    'country' => [
                        'type' => 'string',
                        'description' => 'Country code to filter by'
                    ],
                    'state' => [
                        'type' => 'string',
                        'description' => 'State code to filter by'
                    ]
                ]
            ],
            'annotations' => [
                'title' => 'Get Tax Rates',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    /**
     * Get all tax classes
     */
    public function get_tax_classes($params): array {
        $tax_classes = WC_Tax::get_tax_classes();
        $results = [];
        
        // Add standard class (empty slug)
        $results[] = [
            'slug' => '',
            'name' => 'Standard'
        ];
        
        foreach ($tax_classes as $class) {
            $results[] = [
                'slug' => sanitize_title($class),
                'name' => $class
            ];
        }
        
        return ['tax_classes' => $results, 'total' => count($results)];
    }

    /**
     * Get tax rates
     */
    public function get_tax_rates($params): array {
        global $wpdb;
        
        $where = [];
        $where_values = [];
        
        if (!empty($params['class'])) {
            $where[] = 'tax_rate_class = %s';
            $where_values[] = $params['class'];
        }
        
        if (!empty($params['country'])) {
            $where[] = 'tax_rate_country = %s';
            $where_values[] = $params['country'];
        }
        
        if (!empty($params['state'])) {
            $where[] = 'tax_rate_state = %s';
            $where_values[] = $params['state'];
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $base_query = "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates {$where_clause} ORDER BY tax_rate_order";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($base_query, ...$where_values);
        } else {
            $query = $base_query;
        }
        
        $tax_rates = $wpdb->get_results($query, ARRAY_A);
        $results = [];
        
        foreach ($tax_rates as $rate) {
            $results[] = [
                'id' => $rate['tax_rate_id'],
                'country' => $rate['tax_rate_country'],
                'state' => $rate['tax_rate_state'],
                'postcode' => $rate['tax_rate_postcode'],
                'city' => $rate['tax_rate_city'],
                'rate' => $rate['tax_rate'],
                'name' => $rate['tax_rate_name'],
                'priority' => $rate['tax_rate_priority'],
                'compound' => $rate['tax_rate_compound'],
                'shipping' => $rate['tax_rate_shipping'],
                'order' => $rate['tax_rate_order'],
                'class' => $rate['tax_rate_class']
            ];
        }
        
        return ['tax_rates' => $results, 'total' => count($results)];
    }
}
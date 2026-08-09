<?php
declare(strict_types=1);


namespace McpForWoo\Tools;

use McpForWoo\Core\RegisterMcpTool;

/**
 * Class McpWooTaxes
 * 
 * Provides WooCommerce tax information readonly tools.
 * Only registers tools if WooCommerce is active.
 */
class McpWooTaxes {

    /**
     * Object cache group for tax rate lookups.
     */
    private const CACHE_GROUP = 'mcpfowo_taxes';

    /**
     * How long tax rate lookups stay cached, in seconds.
     */
    private const CACHE_TTL = 300;

    public function __construct() {
        add_action('mcpfowo_init', [$this, 'register_tools']);
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

        // An empty filter matches every row, so the WHERE clause can stay a literal
        // string with every placeholder visible to $wpdb->prepare(). Building the
        // clause dynamically and interpolating it would hide the placeholders.
        $class   = isset($params['class']) ? (string) $params['class'] : '';
        $country = isset($params['country']) ? (string) $params['country'] : '';
        $state   = isset($params['state']) ? (string) $params['state'] : '';

        $cache_key = 'tax_rates_' . md5($class . '|' . $country . '|' . $state);
        $tax_rates = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false === $tax_rates) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- woocommerce_tax_rates is a custom WooCommerce table with no core API for this filter combination; the result is cached below.
            $tax_rates = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
                     WHERE ( %s = '' OR tax_rate_class = %s )
                       AND ( %s = '' OR tax_rate_country = %s )
                       AND ( %s = '' OR tax_rate_state = %s )
                     ORDER BY tax_rate_order",
                    $class,
                    $class,
                    $country,
                    $country,
                    $state,
                    $state
                ),
                ARRAY_A
            );

            wp_cache_set($cache_key, $tax_rates, self::CACHE_GROUP, self::CACHE_TTL);
        }

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

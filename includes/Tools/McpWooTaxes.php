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
            'rest_alias' => [
                'route' => '/wc/v3/taxes/classes',
                'method' => 'GET'
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
            'rest_alias' => [
                'route' => '/wc/v3/taxes',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Tax Rates',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }
}
<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooPaymentGateways
 * 
 * Provides WooCommerce payment gateways information readonly tools.
 * Only registers tools if WooCommerce is active.
 */
class McpWooPaymentGateways {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_payment_gateways',
            'description' => 'Get all available WooCommerce payment gateways (PayPal, Stripe, Bank Transfer, etc.)',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/payment_gateways',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Payment Gateways',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_payment_gateway',
            'description' => 'Get details about a specific WooCommerce payment gateway by ID',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/payment_gateways/(?P<id>[a-zA-Z0-9_-]+)',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Payment Gateway',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }
}
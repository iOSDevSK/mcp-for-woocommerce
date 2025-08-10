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
            'callback' => [$this, 'get_payment_gateways'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object)[],
                'required' => []
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
            'callback' => [$this, 'get_payment_gateway'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'string',
                        'description' => 'Payment Gateway ID',
                        'pattern' => '^[a-zA-Z0-9_-]+$'
                    ]
                ],
                'required' => ['id']
            ],
            'annotations' => [
                'title' => 'Get Payment Gateway',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    /**
     * Get all payment gateways
     */
    public function get_payment_gateways($params): array {
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        $results = [];
        
        foreach ($gateways as $gateway_id => $gateway) {
            $results[] = [
                'id' => $gateway_id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'order' => $gateway->get_order(),
                'enabled' => $gateway->is_enabled(),
                'method_title' => $gateway->get_method_title(),
                'method_description' => $gateway->get_method_description(),
                'has_fields' => $gateway->has_fields(),
                'countries' => $gateway->countries ?? [],
                'availability' => $gateway->availability ?? 'all',
                'icon' => $gateway->get_icon()
            ];
        }
        
        return ['payment_gateways' => $results, 'total' => count($results)];
    }

    /**
     * Get single payment gateway
     */
    public function get_payment_gateway($params): array {
        $gateway_id = $params['id'];
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        
        if (!isset($gateways[$gateway_id])) {
            return ['error' => 'Payment gateway not found'];
        }
        
        $gateway = $gateways[$gateway_id];
        
        return [
            'payment_gateway' => [
                'id' => $gateway_id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'order' => $gateway->get_order(),
                'enabled' => $gateway->is_enabled(),
                'method_title' => $gateway->get_method_title(),
                'method_description' => $gateway->get_method_description(),
                'has_fields' => $gateway->has_fields(),
                'countries' => $gateway->countries ?? [],
                'availability' => $gateway->availability ?? 'all',
                'icon' => $gateway->get_icon(),
                'settings' => $gateway->get_form_fields()
            ]
        ];
    }
}
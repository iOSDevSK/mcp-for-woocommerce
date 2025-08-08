<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooAttributes
 * 
 * Provides WooCommerce product attributes readonly tools.
 * Only registers tools if WooCommerce is active.
 */
class McpWooAttributes {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_product_attributes',
            'description' => 'Get all GLOBAL product attribute definitions (like Color, Size, Material) available in the store. WARNING: This shows attribute types, NOT specific product colors/sizes. To get available colors/sizes for a specific product, use: 1) wc_products_search to find the product, 2) wc_get_product_variations with that product ID.',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/products/attributes',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Product Attributes',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_product_attribute',
            'description' => 'Get a specific WooCommerce product attribute by ID',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/products/attributes/(?P<id>[\d]+)',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Product Attribute',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_attribute_terms',
            'description' => 'Get all terms for a specific product attribute (e.g., Red, Blue for Color attribute)',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/products/attributes/(?P<attribute_id>[\d]+)/terms',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Attribute Terms',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }
}
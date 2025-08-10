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
            'callback' => [$this, 'get_product_attributes'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object)[]
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
            'callback' => [$this, 'get_product_attribute'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Attribute ID',
                        'minimum' => 1
                    ]
                ],
                'required' => ['id']
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
            'callback' => [$this, 'get_attribute_terms'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'attribute_id' => [
                        'type' => 'integer',
                        'description' => 'Attribute ID',
                        'minimum' => 1
                    ]
                ],
                'required' => ['attribute_id']
            ],
            'annotations' => [
                'title' => 'Get Attribute Terms',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    /**
     * Get all product attributes
     */
    public function get_product_attributes($params): array {
        $attributes = wc_get_attribute_taxonomies();
        $results = [];
        
        foreach ($attributes as $attribute) {
            $results[] = [
                'id' => $attribute->attribute_id,
                'name' => $attribute->attribute_name,
                'label' => $attribute->attribute_label,
                'type' => $attribute->attribute_type,
                'orderby' => $attribute->attribute_orderby,
                'has_archives' => (bool) $attribute->attribute_public
            ];
        }
        
        return ['attributes' => $results, 'total' => count($results)];
    }

    /**
     * Get single product attribute
     */
    public function get_product_attribute($params): array {
        $attributes = wc_get_attribute_taxonomies();
        
        foreach ($attributes as $attribute) {
            if ($attribute->attribute_id == $params['id']) {
                return [
                    'attribute' => [
                        'id' => $attribute->attribute_id,
                        'name' => $attribute->attribute_name,
                        'label' => $attribute->attribute_label,
                        'type' => $attribute->attribute_type,
                        'orderby' => $attribute->attribute_orderby,
                        'has_archives' => (bool) $attribute->attribute_public
                    ]
                ];
            }
        }
        
        return ['error' => 'Attribute not found'];
    }

    /**
     * Get attribute terms
     */
    public function get_attribute_terms($params): array {
        $attribute_id = $params['attribute_id'];
        $attribute_taxonomy = wc_attribute_taxonomy_name_by_id($attribute_id);
        
        if (!$attribute_taxonomy) {
            return ['error' => 'Attribute not found'];
        }
        
        $terms = get_terms([
            'taxonomy' => $attribute_taxonomy,
            'hide_empty' => false
        ]);
        
        if (is_wp_error($terms)) {
            return ['error' => $terms->get_error_message()];
        }
        
        $results = [];
        foreach ($terms as $term) {
            $results[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'count' => $term->count
            ];
        }
        
        return ['terms' => $results, 'total' => count($results)];
    }
}
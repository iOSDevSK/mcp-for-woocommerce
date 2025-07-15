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

    /**
     * Resolve attribute name to attribute ID for the wc_get_attribute_terms tool.
     *
     * @param array $args The input arguments.
     * @return array The processed arguments with attribute_id set.
     * @throws \Exception If attribute cannot be found.
     */
    public function resolve_attribute_id(array $args): array {
        // If attribute_id is already provided, use it
        if (isset($args['attribute_id']) && is_numeric($args['attribute_id'])) {
            return ['args' => $args];
        }

        // Check for attribute_name or query parameter
        $attribute_name = $args['attribute_name'] ?? $args['query'] ?? '';
        
        if (empty($attribute_name)) {
            throw new \Exception('Either attribute_id or attribute_name/query parameter is required');
        }

        // Get all product attributes
        $attributes = wc_get_attribute_taxonomies();
        
        $found_attribute = null;
        foreach ($attributes as $attribute) {
            // Check if the name matches the attribute name or slug
            if (strtolower($attribute->attribute_name) === strtolower($attribute_name) ||
                strtolower($attribute->attribute_label) === strtolower($attribute_name)) {
                $found_attribute = $attribute;
                break;
            }
        }

        if (!$found_attribute) {
            throw new \Exception("Attribute '{$attribute_name}' not found. Available attributes: " . 
                implode(', ', array_map(function($attr) { return $attr->attribute_name; }, $attributes)));
        }

        // Set the attribute_id and remove other parameters
        $processed_args = $args;
        $processed_args['attribute_id'] = $found_attribute->attribute_id;
        unset($processed_args['attribute_name'], $processed_args['query']);

        return ['args' => $processed_args];
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_product_attributes',
            'description' => 'Get all WooCommerce product attributes (like Color, Size, Material)',
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
                'route' => '/wc/v3/products/attributes/(?P<id>[\\d]+)',
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
            'description' => 'Get all terms for a specific product attribute (e.g., Red, Blue for Color attribute). You can provide either attribute_id (numeric) or attribute_name (string like "color", "size")',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/products/attributes/(?P<attribute_id>[\\d]+)/terms',
                'method' => 'GET',
                'preCallback' => [$this, 'resolve_attribute_id'],
                'inputSchemaReplacements' => [
                    'properties' => [
                        'attribute_id' => [
                            'type' => 'integer',
                            'description' => 'The numeric ID of the attribute'
                        ],
                        'attribute_name' => [
                            'type' => 'string',
                            'description' => 'The name or slug of the attribute (e.g., "color", "size")'
                        ],
                        'query' => [
                            'type' => 'string',
                            'description' => 'Search query for attribute name (alias for attribute_name)'
                        ]
                    ],
                    'required' => []
                ]
            ],
            'annotations' => [
                'title' => 'Get Attribute Terms',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }
}

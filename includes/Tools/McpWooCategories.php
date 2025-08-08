<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooCategories
 * 
 * Tool for dynamically getting WooCommerce product categories
 */
class McpWooCategories {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_categories',
            'description' => 'Get all available WooCommerce product categories dynamically',
            'type' => 'read',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of categories to retrieve (default: 100)',
                        'default' => 100
                    ],
                    'hide_empty' => [
                        'type' => 'boolean', 
                        'description' => 'Whether to hide categories with no products (default: false)',
                        'default' => false
                    ]
                ],
                'required' => []
            ],
            'callback' => [$this, 'get_categories'],
            'permission_callback' => [$this, 'permission_callback'],
            'annotations' => [
                'title' => 'Get WooCommerce Categories',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    public function get_categories(array $params): array {
        $per_page = $params['per_page'] ?? 100;
        $hide_empty = $params['hide_empty'] ?? false;

        $args = [
            'taxonomy' => 'product_cat',
            'number' => $per_page,
            'hide_empty' => $hide_empty,
            'orderby' => 'name',
            'order' => 'ASC'
        ];

        $categories = get_terms($args);

        if (is_wp_error($categories)) {
            return [
                'error' => [
                    'code' => -32000,
                    'message' => $categories->get_error_message()
                ]
            ];
        }

        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'description' => $category->description,
                'parent' => $category->parent
            ];
        }

        return [
            'categories' => $result,
            'total' => count($result)
        ];
    }

    public function permission_callback(): bool {
        // Allow access when JWT is disabled (read-only mode) or when user has admin privileges
        $jwt_required = function_exists('get_option') ? (bool) get_option('wordpress_mcp_jwt_required', true) : true;
        
        if (!$jwt_required) {
            // JWT disabled - allow public read access to categories
            return true;
        }
        
        // JWT enabled - require admin privileges
        return current_user_can('manage_options');
    }
}
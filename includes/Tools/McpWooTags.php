<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooTags
 * 
 * Tool for dynamically getting WooCommerce product tags
 */
class McpWooTags {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_tags',
            'description' => 'Get all available WooCommerce product tags dynamically',
            'type' => 'read',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of tags to retrieve (default: 100)',
                        'default' => 100
                    ],
                    'hide_empty' => [
                        'type' => 'boolean',
                        'description' => 'Whether to hide tags with no products (default: false)', 
                        'default' => false
                    ]
                ]
            ],
            'callback' => [$this, 'get_tags'],
            'permission_callback' => '__return_true',
            'annotations' => [
                'title' => 'Get WooCommerce Tags',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    public function get_tags(array $params): array {
        $per_page = $params['per_page'] ?? 100;
        $hide_empty = $params['hide_empty'] ?? false;

        $args = [
            'taxonomy' => 'product_tag',
            'number' => $per_page,
            'hide_empty' => $hide_empty,
            'orderby' => 'name',
            'order' => 'ASC'
        ];

        $tags = get_terms($args);

        if (is_wp_error($tags)) {
            return [
                'error' => [
                    'code' => -32000,
                    'message' => $tags->get_error_message()
                ]
            ];
        }

        $result = [];
        foreach ($tags as $tag) {
            $result[] = [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->count,
                'description' => $tag->description
            ];
        }

        return [
            'tags' => $result,
            'total' => count($result)
        ];
    }

}
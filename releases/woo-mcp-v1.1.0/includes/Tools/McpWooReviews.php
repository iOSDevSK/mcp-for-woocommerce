<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooReviews
 * 
 * Provides WooCommerce product reviews readonly tools.
 * Only registers tools if WooCommerce is active.
 */
class McpWooReviews {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_get_product_reviews',
            'description' => 'Get all WooCommerce product reviews with filtering and pagination',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/products/reviews',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Product Reviews',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);

        new RegisterMcpTool([
            'name' => 'wc_get_product_review',
            'description' => 'Get a specific WooCommerce product review by ID',
            'type' => 'read',
            'rest_alias' => [
                'route' => '/wc/v3/products/reviews/(?P<id>[\d]+)',
                'method' => 'GET'
            ],
            'annotations' => [
                'title' => 'Get Product Review',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }
}
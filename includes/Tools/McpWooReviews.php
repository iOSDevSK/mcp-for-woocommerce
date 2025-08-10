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
            'callback' => [$this, 'get_product_reviews'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'Product ID to filter reviews'
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of reviews per page',
                        'default' => 10,
                        'minimum' => 1,
                        'maximum' => 100
                    ]
                ]
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
            'callback' => [$this, 'get_product_review'],
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Review ID',
                        'minimum' => 1
                    ]
                ],
                'required' => ['id']
            ],
            'annotations' => [
                'title' => 'Get Product Review',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    /**
     * Get product reviews
     */
    public function get_product_reviews($params): array {
        $args = [
            'status' => 'approve',
            'number' => $params['per_page'] ?? 10
        ];
        
        if (isset($params['product_id'])) {
            $args['post_id'] = $params['product_id'];
        }
        
        $reviews = get_comments($args);
        $results = [];
        
        foreach ($reviews as $review) {
            $results[] = [
                'id' => $review->comment_ID,
                'product_id' => $review->comment_post_ID,
                'reviewer_name' => $review->comment_author,
                'reviewer_email' => $review->comment_author_email,
                'content' => $review->comment_content,
                'rating' => get_comment_meta($review->comment_ID, 'rating', true),
                'date_created' => $review->comment_date,
                'verified' => get_comment_meta($review->comment_ID, 'verified', true)
            ];
        }
        
        return ['reviews' => $results, 'total' => count($results)];
    }

    /**
     * Get single product review
     */
    public function get_product_review($params): array {
        $review = get_comment($params['id']);
        
        if (!$review || $review->comment_approved !== '1') {
            return ['error' => 'Review not found or not approved'];
        }
        
        return [
            'review' => [
                'id' => $review->comment_ID,
                'product_id' => $review->comment_post_ID,
                'reviewer_name' => $review->comment_author,
                'reviewer_email' => $review->comment_author_email,
                'content' => $review->comment_content,
                'rating' => get_comment_meta($review->comment_ID, 'rating', true),
                'date_created' => $review->comment_date,
                'verified' => get_comment_meta($review->comment_ID, 'verified', true)
            ]
        ];
    }
}
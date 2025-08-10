<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use WP_Query;
use WP_Post;

/**
 * WordPress Pages MCP Tool - Read Only
 */
class McpWordPressPages {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('wordpress_mcp_init', array($this, 'register_tools'));
    }

    /**
     * Register the tools.
     */
    public function register_tools(WpMcp $mcp): void {
        // List WordPress pages
        $mcp->register_tool([
            'name' => 'wordpress_pages_list',
            'description' => 'List WordPress pages with filtering and search options',
            'type' => 'read',
            'callback' => array($this, 'list_pages'),
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'description' => 'Page status filter',
                        'enum' => ['publish', 'draft', 'private', 'future', 'pending', 'any'],
                        'default' => 'publish'
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of pages per page',
                        'default' => 10,
                        'minimum' => 1,
                        'maximum' => 100
                    ],
                    'page' => [
                        'type' => 'integer',
                        'description' => 'Page number',
                        'default' => 1,
                        'minimum' => 1
                    ],
                    'search' => [
                        'type' => 'string',
                        'description' => 'Search term'
                    ],
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Parent page ID (0 for top-level pages)'
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'description' => 'Order by field',
                        'enum' => ['date', 'title', 'menu_order', 'author', 'modified'],
                        'default' => 'menu_order'
                    ],
                    'order' => [
                        'type' => 'string',
                        'description' => 'Order direction',
                        'enum' => ['ASC', 'DESC'],
                        'default' => 'ASC'
                    ]
                ]
            ]
        ]);

        // Get single WordPress page
        $mcp->register_tool([
            'name' => 'wordpress_pages_get',
            'description' => 'Get a single WordPress page by ID',
            'type' => 'read',
            'callback' => array($this, 'get_page'),
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Page ID',
                        'minimum' => 1
                    ]
                ],
                'required' => ['id']
            ]
        ]);
    }

    /**
     * List pages.
     */
    public function list_pages(array $args): array {
        try {
            $query_args = [
                'post_type' => 'page',
                'posts_per_page' => $args['per_page'] ?? 10,
                'paged' => $args['page'] ?? 1,
                'post_status' => $args['status'] ?? 'publish',
                'orderby' => $args['orderby'] ?? 'menu_order',
                'order' => $args['order'] ?? 'ASC'
            ];

            // Add optional filters
            if (!empty($args['search'])) {
                $query_args['s'] = sanitize_text_field($args['search']);
            }

            if (isset($args['parent'])) {
                $query_args['post_parent'] = intval($args['parent']);
            }

            $query = new WP_Query($query_args);
            $pages = [];

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $pages[] = $this->format_page(get_post());
                }
                wp_reset_postdata();
            }

            return [
                'pages' => $pages,
                'pagination' => [
                    'current_page' => $query_args['paged'],
                    'total_pages' => $query->found_posts,
                    'total_pages_count' => $query->max_num_pages,
                    'per_page' => $query_args['posts_per_page']
                ]
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve pages: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get single page.
     */
    public function get_page(array $args): array {
        try {
            $page_id = intval($args['id']);
            $page = get_post($page_id);

            if (!$page || $page->post_type !== 'page') {
                return [
                    'error' => 'Page not found or invalid post type',
                    'page_id' => $page_id
                ];
            }

            return [
                'page' => $this->format_page($page)
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve page: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format page data.
     */
    private function format_page(WP_Post $page): array {
        $author = get_userdata($page->post_author);
        $parent = $page->post_parent ? get_post($page->post_parent) : null;
        $children = get_children([
            'post_parent' => $page->ID,
            'post_type' => 'page',
            'post_status' => 'publish'
        ]);
        $featured_image = get_the_post_thumbnail_url($page->ID, 'full');

        return [
            'id' => $page->ID,
            'title' => $page->post_title,
            'content' => $page->post_content,
            'excerpt' => $page->post_excerpt,
            'status' => $page->post_status,
            'slug' => $page->post_name,
            'date' => $page->post_date,
            'date_gmt' => $page->post_date_gmt,
            'modified' => $page->post_modified,
            'modified_gmt' => $page->post_modified_gmt,
            'link' => get_permalink($page->ID),
            'menu_order' => $page->menu_order,
            'parent' => $parent ? [
                'id' => $parent->ID,
                'title' => $parent->post_title,
                'link' => get_permalink($parent->ID)
            ] : null,
            'children' => array_map(function($child) {
                return [
                    'id' => $child->ID,
                    'title' => $child->post_title,
                    'link' => get_permalink($child->ID),
                    'menu_order' => $child->menu_order
                ];
            }, $children),
            'author' => [
                'id' => $author->ID,
                'name' => $author->display_name,
                'login' => $author->user_login,
                'email' => $author->user_email
            ],
            'featured_image' => $featured_image ?: null,
            'template' => get_page_template_slug($page->ID),
            'comment_count' => intval($page->comment_count)
        ];
    }

}
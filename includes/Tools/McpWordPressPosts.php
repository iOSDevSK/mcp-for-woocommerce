<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\WpMcp;
use WP_Query;
use WP_Post;

/**
 * WordPress Posts MCP Tool - Read Only
 */
class McpWordPressPosts {

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
        // List WordPress posts
        $mcp->register_tool([
            'name' => 'wordpress_posts_list',
            'description' => 'List WordPress posts with filtering and search options',
            'type' => 'read',
            'callback' => array($this, 'list_posts'),
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'description' => 'Post status filter',
                        'enum' => ['publish', 'draft', 'private', 'future', 'pending', 'any'],
                        'default' => 'publish'
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of posts per page',
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
                    'category' => [
                        'type' => 'string',
                        'description' => 'Category slug or ID'
                    ],
                    'tag' => [
                        'type' => 'string',
                        'description' => 'Tag slug or ID'
                    ],
                    'author' => [
                        'type' => 'integer',
                        'description' => 'Author ID'
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'description' => 'Order by field',
                        'enum' => ['date', 'title', 'menu_order', 'author', 'modified'],
                        'default' => 'date'
                    ],
                    'order' => [
                        'type' => 'string',
                        'description' => 'Order direction',
                        'enum' => ['ASC', 'DESC'],
                        'default' => 'DESC'
                    ]
                ]
            ]
        ]);

        // Get single WordPress post
        $mcp->register_tool([
            'name' => 'wordpress_posts_get',
            'description' => 'Get a single WordPress post by ID',
            'type' => 'read',
            'callback' => array($this, 'get_post'),
            'permission_callback' => '__return_true',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'Post ID',
                        'minimum' => 1
                    ]
                ],
                'required' => ['id']
            ]
        ]);
    }

    /**
     * List posts.
     */
    public function list_posts(array $args): array {
        try {
            $query_args = [
                'post_type' => 'post',
                'posts_per_page' => $args['per_page'] ?? 10,
                'paged' => $args['page'] ?? 1,
                'post_status' => $args['status'] ?? 'publish',
                'orderby' => $args['orderby'] ?? 'date',
                'order' => $args['order'] ?? 'DESC'
            ];

            // Add optional filters
            if (!empty($args['search'])) {
                $query_args['s'] = sanitize_text_field($args['search']);
            }

            if (!empty($args['category'])) {
                if (is_numeric($args['category'])) {
                    $query_args['cat'] = intval($args['category']);
                } else {
                    $query_args['category_name'] = sanitize_text_field($args['category']);
                }
            }

            if (!empty($args['tag'])) {
                if (is_numeric($args['tag'])) {
                    $query_args['tag_id'] = intval($args['tag']);
                } else {
                    $query_args['tag'] = sanitize_text_field($args['tag']);
                }
            }

            if (!empty($args['author'])) {
                $query_args['author'] = intval($args['author']);
            }

            $query = new WP_Query($query_args);
            $posts = [];

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $posts[] = $this->format_post(get_post());
                }
                wp_reset_postdata();
            }

            return [
                'posts' => $posts,
                'pagination' => [
                    'current_page' => $query_args['paged'],
                    'total_posts' => $query->found_posts,
                    'total_pages' => $query->max_num_pages,
                    'per_page' => $query_args['posts_per_page']
                ]
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve posts: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get single post.
     */
    public function get_post(array $args): array {
        try {
            $post_id = intval($args['id']);
            $post = get_post($post_id);

            if (!$post || $post->post_type !== 'post') {
                return [
                    'error' => 'Post not found or invalid post type',
                    'post_id' => $post_id
                ];
            }

            return [
                'post' => $this->format_post($post)
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve post: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format post data.
     */
    private function format_post(WP_Post $post): array {
        $author = get_userdata($post->post_author);
        $categories = get_the_category($post->ID);
        $tags = get_the_tags($post->ID);
        $featured_image = get_the_post_thumbnail_url($post->ID, 'full');

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'slug' => $post->post_name,
            'date' => $post->post_date,
            'date_gmt' => $post->post_date_gmt,
            'modified' => $post->post_modified,
            'modified_gmt' => $post->post_modified_gmt,
            'link' => get_permalink($post->ID),
            'author' => [
                'id' => $author->ID,
                'name' => $author->display_name,
                'login' => $author->user_login,
                'email' => $author->user_email
            ],
            'categories' => array_map(function($cat) {
                return [
                    'id' => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug
                ];
            }, $categories ?: []),
            'tags' => array_map(function($tag) {
                return [
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'slug' => $tag->slug
                ];
            }, $tags ?: []),
            'featured_image' => $featured_image ?: null,
            'comment_count' => intval($post->comment_count)
        ];
    }

}
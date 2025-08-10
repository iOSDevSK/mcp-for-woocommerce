<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;

/**
 * Class McpWooIntentAnalyzer
 * 
 * Universal intent analyzer for WooCommerce search queries
 */
class McpWooIntentAnalyzer {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpTool([
            'name' => 'wc_analyze_search_intent',
            'description' => 'Analyze user search query and suggest optimal WooCommerce search parameters',
            'type' => 'read',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'user_query' => [
                        'type' => 'string',
                        'description' => 'The user search query to analyze'
                    ],
                    'available_categories' => [
                        'type' => 'array',
                        'description' => 'Available product categories from wc_get_categories',
                        'items' => [
                            'type' => 'object'
                        ],
                        'default' => []
                    ],
                    'available_tags' => [
                        'type' => 'array', 
                        'description' => 'Available product tags from wc_get_tags',
                        'items' => [
                            'type' => 'object'
                        ],
                        'default' => []
                    ]
                ],
                'required' => ['user_query']
            ],
            'callback' => [$this, 'analyze_intent'],
            'permission_callback' => '__return_true',
            'annotations' => [
                'title' => 'Analyze Search Intent',
                'readOnlyHint' => true,
                'openWorldHint' => false
            ]
        ]);
    }

    public function analyze_intent(array $params): array {
        $user_query = $params['user_query'] ?? '';
        $available_categories = $params['available_categories'] ?? [];
        $available_tags = $params['available_tags'] ?? [];

        if (empty($user_query)) {
            return [
                'error' => [
                    'code' => -32000,
                    'message' => 'User query is required'
                ]
            ];
        }

        $query = strtolower($user_query);
        
        $intent = [
            'search_params' => [],
            'reasoning' => []
        ];

        // Universal price patterns (SK/EN)
        $price_patterns = [
            'cheapest' => '/\b(naj.*lacn|cheap|low.*price|minim|least.*expens|affordabl)\w*/i',
            'expensive' => '/\b(naj.*drah|expens|high.*price|maxim|most.*cost|premium)\w*/i'
        ];

        if (preg_match($price_patterns['cheapest'], $query)) {
            $intent['search_params']['orderby'] = 'price';
            $intent['search_params']['order'] = 'asc';
            $intent['reasoning'][] = 'Detected price sorting: cheapest first';
        }

        if (preg_match($price_patterns['expensive'], $query)) {
            $intent['search_params']['orderby'] = 'price'; 
            $intent['search_params']['order'] = 'desc';
            $intent['reasoning'][] = 'Detected price sorting: most expensive first';
        }

        // Universal sale patterns
        $sale_pattern = '/\b(zľav|sale|akci|discount|promo|offer|deal|reduc|special)\w*/i';
        if (preg_match($sale_pattern, $query)) {
            $intent['search_params']['meta_query'] = [
                [
                    'key' => '_sale_price',
                    'value' => '',
                    'compare' => '!='
                ]
            ];
            $intent['reasoning'][] = 'Detected sale/discount intent';
        }

        // Universal newest patterns
        $newest_pattern = '/\b(naj.*nov|newest|latest|new|recent|fresh)\w*/i';
        if (preg_match($newest_pattern, $query)) {
            $intent['search_params']['orderby'] = 'date';
            $intent['search_params']['order'] = 'desc';
            $intent['reasoning'][] = 'Detected newest products intent';
        }

        // Universal best rated patterns
        $rating_pattern = '/\b(naj.*lep|best|top.*rat|high.*rat|excellent|quality)\w*/i';
        if (preg_match($rating_pattern, $query)) {
            $intent['search_params']['orderby'] = 'rating';
            $intent['search_params']['order'] = 'desc';
            $intent['reasoning'][] = 'Detected best rated products intent';
        }

        // Category matching with fuzzy search
        $category_match = $this->find_best_category_match($query, $available_categories);
        if ($category_match) {
            $intent['search_params']['category'] = $category_match['category']['id'];
            $intent['reasoning'][] = sprintf(
                'Category match: %s (confidence: %.2f)',
                $category_match['category']['name'],
                $category_match['score']
            );
        }

        // Tag matching if no category found
        if (!isset($intent['search_params']['category'])) {
            $tag_match = $this->find_best_tag_match($query, $available_tags);
            if ($tag_match) {
                $intent['search_params']['tag'] = $tag_match['tag']['id'];
                $intent['reasoning'][] = sprintf(
                    'Tag match: %s (confidence: %.2f)',
                    $tag_match['tag']['name'], 
                    $tag_match['score']
                );
            }
        }

        // Default settings
        if (!isset($intent['search_params']['per_page'])) {
            $intent['search_params']['per_page'] = 20;
        }

        return $intent;
    }

    private function find_best_category_match(string $query, array $categories): ?array {
        if (empty($categories)) {
            return null;
        }

        $query_words = explode(' ', strtolower($query));
        $query_words = array_filter($query_words, function($word) {
            return strlen($word) >= 3; // Min 3 chars
        });

        $best_match = null;
        $best_score = 0;

        foreach ($categories as $category) {
            $cat_name = strtolower($category['name']);
            $cat_slug = strtolower($category['slug']);

            foreach ($query_words as $word) {
                // Exact match
                if (strpos($cat_name, $word) !== false || strpos($cat_slug, $word) !== false) {
                    return [
                        'category' => $category,
                        'score' => 1.0,
                        'type' => 'exact'
                    ];
                }

                // Similarity matching
                $name_score = $this->calculate_similarity($word, $cat_name);
                $slug_score = $this->calculate_similarity($word, $cat_slug);
                $max_score = max($name_score, $slug_score);

                if ($max_score > 0.6 && $max_score > $best_score) {
                    $best_score = $max_score;
                    $best_match = [
                        'category' => $category,
                        'score' => $max_score,
                        'type' => 'fuzzy'
                    ];
                }
            }
        }

        return $best_match;
    }

    private function find_best_tag_match(string $query, array $tags): ?array {
        if (empty($tags)) {
            return null;
        }

        $query_words = explode(' ', strtolower($query));
        $query_words = array_filter($query_words, function($word) {
            return strlen($word) >= 3;
        });

        $best_match = null;
        $best_score = 0;

        foreach ($tags as $tag) {
            $tag_name = strtolower($tag['name']);
            $tag_slug = strtolower($tag['slug']);

            foreach ($query_words as $word) {
                if (strpos($tag_name, $word) !== false || strpos($tag_slug, $word) !== false) {
                    return [
                        'tag' => $tag,
                        'score' => 1.0,
                        'type' => 'exact'
                    ];
                }

                $name_score = $this->calculate_similarity($word, $tag_name);
                $slug_score = $this->calculate_similarity($word, $tag_slug);
                $max_score = max($name_score, $slug_score);

                if ($max_score > 0.6 && $max_score > $best_score) {
                    $best_score = $max_score;
                    $best_match = [
                        'tag' => $tag,
                        'score' => $max_score,
                        'type' => 'fuzzy'
                    ];
                }
            }
        }

        return $best_match;
    }

    private function calculate_similarity(string $str1, string $str2): float {
        $longer = strlen($str1) > strlen($str2) ? $str1 : $str2;
        $shorter = strlen($str1) > strlen($str2) ? $str2 : $str1;

        if (strlen($longer) === 0) {
            return 1.0;
        }

        $edit_distance = levenshtein($longer, $shorter);
        return (strlen($longer) - $edit_distance) / strlen($longer);
    }

}
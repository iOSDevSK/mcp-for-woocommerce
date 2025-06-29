<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Resources;

use Automattic\WordpressMcp\Core\RegisterMcpResource;

/**
 * Class McpWooSearchGuide
 * 
 * Resource providing guidance for LLM on how to use WooCommerce search tools effectively
 */
class McpWooSearchGuide {

    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_resource']);
    }

    public function register_resource(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        new RegisterMcpResource(
            [
                'uri' => 'woocommerce://search-guide',
                'name' => 'woocommerce-search-guide',
                'description' => 'Comprehensive guide for AI assistants on how to perform intelligent WooCommerce product searches using the available tools',
                'mimeType' => 'application/json'
            ],
            [$this, 'get_search_guide']
        );
    }

    public function get_search_guide(array $params = []): array {
        return [
            'title' => 'WooCommerce Intelligent Search Guide',
            'version' => '1.0',
            'description' => 'Step-by-step guide for AI assistants to perform optimal product searches',
            
            'workflow' => [
                'overview' => 'Always follow this 4-step process for any product search query',
                'steps' => [
                    [
                        'step' => 1,
                        'action' => 'Read this guide',
                        'description' => 'Understand the available tools and workflow',
                        'tool' => 'resources/read',
                        'uri' => 'woocommerce://search-guide'
                    ],
                    [
                        'step' => 2,
                        'action' => 'Discover available categories and tags',
                        'description' => 'Get the current store categories and tags to understand what products are available',
                        'tools' => [
                            'wc_get_categories' => 'Get all product categories with IDs, names, and counts',
                            'wc_get_tags' => 'Get all product tags with IDs, names, and counts'
                        ],
                        'parameters' => [
                            'per_page' => 100,
                            'hide_empty' => false
                        ]
                    ],
                    [
                        'step' => 3,
                        'action' => 'Analyze search intent',
                        'description' => 'Use the universal intent analyzer to get optimal search parameters',
                        'tool' => 'wc_analyze_search_intent',
                        'required_parameters' => [
                            'user_query' => 'The original user search query'
                        ],
                        'recommended_parameters' => [
                            'available_categories' => 'Array from wc_get_categories',
                            'available_tags' => 'Array from wc_get_tags'
                        ]
                    ],
                    [
                        'step' => 4,
                        'action' => 'Execute optimized search',
                        'description' => 'Use the suggested parameters from intent analysis to search products',
                        'tool' => 'wc_products_search',
                        'parameters' => 'Use the search_params from wc_analyze_search_intent'
                    ]
                ]
            ],

            'intent_patterns' => [
                'price_sorting' => [
                    'cheapest' => [
                        'keywords' => ['cheapest', 'cheap', 'low price', 'minimum', 'affordable', 'budget'],
                        'parameters' => ['orderby' => 'price', 'order' => 'asc']
                    ],
                    'expensive' => [
                        'keywords' => ['expensive', 'high price', 'premium', 'luxury', 'costly'],
                        'parameters' => ['orderby' => 'price', 'order' => 'desc']
                    ]
                ],
                'temporal_sorting' => [
                    'newest' => [
                        'keywords' => ['newest', 'latest', 'recent', 'fresh', 'new', 'current'],
                        'parameters' => ['orderby' => 'date', 'order' => 'desc']
                    ]
                ],
                'quality_sorting' => [
                    'best_rated' => [
                        'keywords' => ['best', 'top rated', 'excellent', 'quality', 'highest rated'],
                        'parameters' => ['orderby' => 'rating', 'order' => 'desc']
                    ]
                ],
                'promotional' => [
                    'on_sale' => [
                        'keywords' => ['sale', 'discount', 'promo', 'offer', 'deal', 'reduced', 'clearance'],
                        'parameters' => ['meta_query' => [['key' => '_sale_price', 'value' => '', 'compare' => '!=']]]
                    ]
                ]
            ],

            'category_matching' => [
                'strategy' => 'The intent analyzer uses fuzzy matching to find relevant categories',
                'similarity_threshold' => 0.6,
                'methods' => [
                    'exact_match' => 'Direct string contains check (highest priority)',
                    'fuzzy_match' => 'Levenshtein distance calculation for typos and variations',
                    'partial_match' => 'Substring matching for related terms'
                ],
                'examples' => [
                    'perfume → Perfumes (exact match)',
                    'perfums → Perfumes (fuzzy match)', 
                    'cosmetics → Foundation, Powder, etc. (partial match)'
                ]
            ],

            'best_practices' => [
                'always_get_categories_first' => 'Categories change dynamically, never assume what categories exist',
                'use_intent_analyzer' => 'Always analyze user intent before searching - it provides optimized parameters',
                'combine_multiple_intents' => 'Users often combine price + category + promotional intent in one query',
                'fallback_strategy' => 'If intent analyzer finds no category match, search by general terms',
                'handle_no_results' => 'If search returns empty, try broader parameters or suggest alternatives'
            ],

            'common_patterns' => [
                'price_with_category' => [
                    'example' => 'cheapest perfumes',
                    'workflow' => 'Get categories → Analyze intent → Search with price+category filters'
                ],
                'promotional_search' => [
                    'example' => 'cosmetics on sale',
                    'workflow' => 'Get categories → Analyze intent → Search with sale filter + category'
                ],
                'brand_search' => [
                    'example' => 'Davidoff perfumes',
                    'workflow' => 'Get categories+tags → Analyze intent → Search with brand tag + category'
                ],
                'new_products' => [
                    'example' => 'newest products',
                    'workflow' => 'Analyze intent → Search with date ordering'
                ]
            ],

            'error_handling' => [
                'no_categories_found' => 'If wc_get_categories fails, proceed with basic search',
                'intent_analysis_fails' => 'Use basic search parameters as fallback',
                'no_search_results' => 'Try broader search terms or remove some filters',
                'invalid_category_id' => 'Validate category IDs from the categories list'
            ],

            'performance_tips' => [
                'cache_categories' => 'Categories rarely change, can be cached during session',
                'limit_results' => 'Use per_page parameter to control response size',
                'progressive_search' => 'Start with specific filters, broaden if no results',
                'combine_calls' => 'Get categories and tags in parallel when possible'
            ],

            'examples' => [
                [
                    'user_query' => 'cheapest perfumes on sale',
                    'expected_workflow' => [
                        '1. wc_get_categories',
                        '2. wc_analyze_search_intent with categories',
                        '3. wc_products_search with price asc + sale filter + perfumes category'
                    ],
                    'expected_parameters' => [
                        'orderby' => 'price',
                        'order' => 'asc',
                        'category' => '28',
                        'meta_query' => [['key' => '_sale_price', 'compare' => '!=']]
                    ]
                ],
                [
                    'user_query' => 'newest lipstick',
                    'expected_workflow' => [
                        '1. wc_get_categories',
                        '2. wc_analyze_search_intent with categories', 
                        '3. wc_products_search with date desc + lipstick category'
                    ],
                    'expected_parameters' => [
                        'orderby' => 'date',
                        'order' => 'desc',
                        'category' => '26'
                    ]
                ]
            ],

            'troubleshooting' => [
                'empty_results' => [
                    'causes' => ['Too restrictive filters', 'No products in category', 'No sale products'],
                    'solutions' => ['Remove sale filter', 'Broaden category', 'Try related categories']
                ],
                'wrong_category' => [
                    'causes' => ['Fuzzy matching failed', 'Category name mismatch'],
                    'solutions' => ['Check available categories', 'Use broader search terms', 'Manual category selection']
                ],
                'slow_performance' => [
                    'causes' => ['Large result sets', 'Complex queries'],
                    'solutions' => ['Reduce per_page', 'Add more specific filters', 'Use pagination']
                ]
            ]
        ];
    }
}
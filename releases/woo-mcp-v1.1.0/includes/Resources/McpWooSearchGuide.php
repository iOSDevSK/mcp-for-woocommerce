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
              'description' => 'Universal guide for AI assistants on how to perform intelligent WooCommerce product searches using the available tools',
              'mimeType' => 'application/json'
          ],
          [$this, 'get_search_guide']
      );
  }

  public function get_search_guide(array $params = []): array {
      return [
          'title' => 'WooCommerce Universal Search Guide',
          'version' => '2.0',
          'description' => 'Universal step-by-step guide for AI assistants to perform optimal product searches with automatic fallback strategies for ANY store type: electronics, food, pets, pharmacy, automotive, clothing, books, tools, beauty, sports, etc. Handles multiple products with same name.',
          
          'workflow' => [
              'overview' => 'Always follow this 4-step process for ANY product search query with intelligent fallbacks',
              'universal_principle' => 'This guide works for ALL store types: electronics, food, pets, pharmacy, automotive, clothing, books, tools, beauty, sports, home & garden, toys, etc.',
              'critical_workflow_rule' => 'ALWAYS search for products by name using wc_products_search FIRST to get correct product IDs. NEVER use hardcoded IDs.',
              'multiple_products_handling' => 'If multiple products have same name (e.g., Men vs Women versions), present all options to user with their differences.',
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
                      'action' => 'Execute intelligent search with automatic fallbacks',
                      'description' => 'Multi-stage search strategy that automatically falls back when no results found',
                      'primary_tool' => 'wc_products_search',
                      'mandatory_rule' => 'NEVER return empty results - always try fallback strategies',
                      'fallback_strategy' => [
                          'stage_1_primary' => [
                              'description' => 'Search with all intent analysis parameters (full search)',
                              'tool' => 'wc_products_search',
                              'use_parameters' => 'Complete parameters from wc_analyze_search_intent including filters',
                              'example' => 'Search for "[product] on sale" with matched category + sale filter + price sorting'
                          ],
                          'stage_2_category_only' => [
                              'description' => 'IF stage_1 returns 0 results → Remove promotional/price filters, keep only categories',
                              'tool' => 'wc_products_search',
                              'use_parameters' => 'Only category filters, remove on_sale, price sorting, meta_query filters',
                              'trigger' => 'When primary search returns empty array',
                              'example' => 'Search for "[product]" category only, ignore sale/price filters'
                          ],
                          'stage_3_broader_categories' => [
                              'description' => 'IF stage_2 returns 0 results → Search in related/parent categories',
                              'tool' => 'wc_products_search',
                              'use_parameters' => 'Broader category terms, parent categories, related categories',
                              'trigger' => 'When category-only search returns empty array',
                              'example' => 'If "[specific_category]" empty, try "[parent_category]" or related categories'
                          ],
                          'stage_4_general_search' => [
                              'description' => 'IF stage_3 returns 0 results → General text search without category filters',
                              'tool' => 'wc_products_search',
                              'use_parameters' => 'Only search parameter with original query terms, no filters',
                              'trigger' => 'When broader categories return empty array',
                              'example' => 'Search for "[search_term]" as general text search across all products'
                          ],
                          'stage_5_show_alternatives' => [
                              'description' => 'IF all stages return 0 results → Show available categories or popular products',
                              'tool' => 'wc_get_categories',
                              'use_parameters' => 'Show available categories that contain products',
                              'trigger' => 'When all product searches return empty',
                              'example' => 'Show message "No [product] found, but we have: [available_categories]"'
                          ]
                      ],
                      'implementation_notes' => [
                          'Check each stage result count before proceeding to next stage',
                          'If any stage returns products, stop and present those results',
                          'Always inform user which search strategy was successful',
                          'For empty results, explain what was tried and suggest alternatives'
                      ]
                  ]
              ]
          ],

          'intent_patterns' => [
              'price_sorting' => [
                  'cheapest' => [
                      'keywords' => ['cheapest', 'cheap', 'low price', 'minimum', 'affordable', 'budget', 'lowest'],
                      'parameters' => ['orderby' => 'price', 'order' => 'asc'],
                      'fallback_removal' => 'Remove in stage_2 if no results found'
                  ],
                  'expensive' => [
                      'keywords' => ['expensive', 'high price', 'premium', 'luxury', 'costly', 'highest', 'most expensive'],
                      'parameters' => ['orderby' => 'price', 'order' => 'desc'],
                      'fallback_removal' => 'Remove in stage_2 if no results found'
                  ]
              ],
              'temporal_sorting' => [
                  'newest' => [
                      'keywords' => ['newest', 'latest', 'recent', 'fresh', 'new', 'current', 'just arrived'],
                      'parameters' => ['orderby' => 'date', 'order' => 'desc'],
                      'fallback_removal' => 'Remove in stage_2 if no results found'
                  ]
              ],
              'quality_sorting' => [
                  'best_rated' => [
                      'keywords' => ['best', 'top rated', 'excellent', 'quality', 'highest rated', 'popular', 'recommended'],
                      'parameters' => ['orderby' => 'rating', 'order' => 'desc'],
                      'fallback_removal' => 'Remove in stage_2 if no results found'
                  ]
              ],
              'promotional' => [
                  'on_sale' => [
                      'keywords' => ['sale', 'discount', 'promo', 'offer', 'deal', 'reduced', 'clearance', 'special offer'],
                      'parameters' => ['meta_query' => [['key' => '_sale_price', 'value' => '', 'compare' => '!=']]],
                      'fallback_removal' => 'ALWAYS remove in stage_2 - this is often the cause of empty results'
                  ]
              ]
          ],

          'category_matching' => [
              'strategy' => 'The intent analyzer uses fuzzy matching to find relevant categories for ANY product type',
              'similarity_threshold' => 0.6,
              'methods' => [
                  'exact_match' => 'Direct string contains check (highest priority)',
                  'fuzzy_match' => 'Levenshtein distance calculation for typos and variations',
                  'partial_match' => 'Substring matching for related terms'
              ],
              'universal_examples' => [
                  '[search_term] → [matched_category] (exact match)',
                  '[search_with_typo] → [correct_category] (fuzzy match)', 
                  '[broad_term] → [related_categories] (partial match)'
              ],
              'concrete_examples' => [
                  'laptop → Laptops',
                  'tshirt → T-Shirts', 
                  'book → Books',
                  'shoes → Footwear',
                  'phone → Electronics'
              ],
              'fallback_strategy' => [
                  'try_parent_categories' => 'If specific category not found, try broader parent categories',
                  'try_related_categories' => 'If exact match fails, try semantically related categories',
                  'try_broader_terms' => 'If specific category not found, use general search terms'
              ]
          ],

          'best_practices' => [
              'always_search_first' => 'CRITICAL: Always use wc_products_search FIRST to find products by name before using any other tools',
              'never_hardcode_ids' => 'NEVER use hardcoded product IDs - always get IDs from search results',
              'handle_multiple_products' => 'If search returns multiple products with same name, present all options to user',
              'get_variations_correctly' => 'To get product colors/sizes: 1) Search for product, 2) Use wc_get_product_variations with the found product_id',
              'always_get_categories_first' => 'Categories change dynamically, never assume what categories exist',
              'use_intent_analyzer' => 'Always analyze user intent before searching - it provides optimized parameters',
              'combine_multiple_intents' => 'Users often combine price + category + promotional intent in one query',
              'intelligent_fallback_strategy' => 'Always implement 5-stage fallback: full search → category only → broader categories → general search → show alternatives',
              'never_return_empty' => 'If all searches fail, always suggest related categories or explain what products are available',
              'progressive_filter_removal' => 'Remove most restrictive filters first (sale, price) then broader filters',
              'inform_user_of_strategy' => 'Tell user which search strategy worked (e.g., "Found products in [category] instead")',
              'handle_no_results_gracefully' => 'Always explain what was searched and offer alternatives',
              'universal_approach' => 'This strategy works for ANY store type: electronics, food, pets, pharmacy, automotive, clothing, books, tools, beauty, sports, etc.'
          ],

          'common_patterns' => [
              'price_with_category' => [
                  'pattern' => 'cheapest [product_type]',
                  'examples' => ['cheapest laptops', 'cheapest books', 'cheapest shoes'],
                  'workflow' => 'Get categories → Analyze intent → Search with price+category filters → If empty, remove price filter',
                  'fallback' => 'If no cheap [products], show all [products]'
              ],
              'promotional_search' => [
                  'pattern' => '[product_type] on sale',
                  'examples' => ['electronics on sale', 'clothing on sale', 'books on discount'],
                  'workflow' => 'Get categories → Analyze intent → Search with sale filter + category → If empty, remove sale filter',
                  'fallback' => 'If no [products] on sale, show all [products]'
              ],
              'brand_search' => [
                  'pattern' => '[brand] [product_type]',
                  'examples' => ['Samsung phones', 'Nike shoes', 'Apple laptops'],
                  'workflow' => 'Get categories+tags → Analyze intent → Search with brand tag + category → If empty, search category only',
                  'fallback' => 'If no [brand] [products], show all [products]'
              ],
              'new_products' => [
                  'pattern' => 'newest [product_type]',
                  'examples' => ['newest electronics', 'latest books', 'new arrivals'],
                  'workflow' => 'Analyze intent → Search with date ordering → If empty, show popular products',
                  'fallback' => 'If no new [products], show featured [products]'
              ]
          ],

          'error_handling' => [
              'no_categories_found' => 'If wc_get_categories fails, proceed with basic search',
              'intent_analysis_fails' => 'Use basic search parameters as fallback',
              'progressive_fallback' => [
                  'step_1_failed' => 'Remove promotional filters (on_sale, discount) - most common cause',
                  'step_2_failed' => 'Remove price sorting and search in broader categories', 
                  'step_3_failed' => 'Remove category filters and do general text search',
                  'step_4_failed' => 'Show available categories and suggest alternatives',
                  'all_failed' => 'List available categories and popular products'
              ],
              'invalid_category_id' => 'Validate category IDs from the categories list',
              'empty_search_terms' => 'If search terms are empty, show popular or featured products'
          ],

          'performance_tips' => [
              'cache_categories' => 'Categories rarely change, can be cached during session',
              'limit_results' => 'Use per_page parameter to control response size',
              'progressive_search' => 'Start with specific filters, broaden systematically if no results',
              'combine_calls' => 'Get categories and tags in parallel when possible',
              'early_exit' => 'Stop fallback chain as soon as any stage returns results'
          ],

          'universal_examples' => [
              [
                  'pattern' => 'cheapest [product] on sale',
                  'concrete_examples' => ['cheapest laptops on sale', 'cheapest shoes on discount', 'cheapest books on offer'],
                  'expected_workflow' => [
                      '1. wc_get_categories',
                      '2. wc_analyze_search_intent with categories',
                      '3. Stage 1: wc_products_search with price asc + sale filter + [product] category',
                      '4. IF empty → Stage 2: wc_products_search with only [product] category (remove sale filter)',
                      '5. IF empty → Stage 3: wc_products_search in broader parent category',
                      '6. Present results from first successful stage'
                  ],
                  'expected_parameters' => [
                      'stage_1' => [
                          'orderby' => 'price',
                          'order' => 'asc',
                          'category' => '[matched_category_id]',
                          'meta_query' => [['key' => '_sale_price', 'compare' => '!=']]
                      ],
                      'stage_2_fallback' => [
                          'orderby' => 'price',
                          'order' => 'asc',
                          'category' => '[matched_category_id]'
                      ]
                  ]
              ],
              [
                  'pattern' => 'newest [product]',
                  'concrete_examples' => ['newest electronics', 'latest fashion', 'new books'],
                  'expected_workflow' => [
                      '1. wc_get_categories',
                      '2. wc_analyze_search_intent with categories', 
                      '3. Stage 1: wc_products_search with date desc + [product] category',
                      '4. IF empty → Stage 2: wc_products_search in parent category',
                      '5. IF empty → Stage 3: general search for "[product]"'
                  ],
                  'expected_parameters' => [
                      'stage_1' => [
                          'orderby' => 'date',
                          'order' => 'desc',
                          'category' => '[matched_category_id]'
                      ],
                      'stage_2_fallback' => [
                          'orderby' => 'date',
                          'order' => 'desc',
                          'category' => '[parent_category_id]'
                      ]
                  ]
              ],
              [
                  'pattern' => '[product] in discount',
                  'concrete_examples' => ['electronics in discount', 'clothing on sale', 'books with special offers'],
                  'expected_workflow' => [
                      '1. wc_get_categories → Find [product] category',
                      '2. wc_analyze_search_intent → Detect sale intent + [product] category',
                      '3. Stage 1: Search [products] on sale → IF EMPTY RESULT',
                      '4. Stage 2: Search all [products] (remove sale filter) → Show available [products]',
                      '5. Inform user: "No [products] currently on sale, but here are available [products]"'
                  ]
              ]
          ],

          'product_variations_workflow' => [
              'description' => 'How to get product colors, sizes, and other variations correctly',
              'critical_rule' => 'NEVER use hardcoded product IDs - always search first',
              'correct_workflow' => [
                  'step_1' => [
                      'action' => 'Search for the product by name',
                      'tool' => 'wc_products_search',
                      'example' => 'search="Ramie Shirt with Pockets"',
                      'result' => 'Returns product(s) with correct ID(s)'
                  ],
                  'step_2' => [
                      'action' => 'Handle multiple products if found',
                      'description' => 'If multiple products have same name (e.g., Men/Women versions), present all options',
                      'example' => 'Found 2 "Ramie Shirt with Pockets": ID 1169 (Men), ID 1170 (Women)'
                  ],
                  'step_3' => [
                      'action' => 'Get variations for each relevant product',
                      'tool' => 'wc_get_product_variations',
                      'parameter' => 'product_id from search results',
                      'example' => 'wc_get_product_variations(product_id=1169) for Men version'
                  ],
                  'step_4' => [
                      'action' => 'Present colors/sizes to user',
                      'description' => 'Show available options with prices and stock status',
                      'example' => 'Men Ramie Shirt available in: White (€39.99), Yellow (€39.99)'
                  ]
              ],
              'wrong_approaches' => [
                  'using_global_attributes' => 'wc_get_product_attributes shows global attribute types, not specific product colors',
                  'hardcoded_ids' => 'Using product_id=42 or any hardcoded ID without searching first',
                  'skipping_search' => 'Going directly to variations without finding the correct product first'
              ],
              'universal_examples' => [
                  'clothing' => 'Search "Blue Jeans" → Get product ID → Get variations for sizes/styles',
                  'electronics' => 'Search "iPhone 15" → Get product ID → Get variations for colors/storage',
                  'food' => 'Search "Organic Coffee" → Get product ID → Get variations for sizes/flavors',
                  'automotive' => 'Search "Car Tires" → Get product ID → Get variations for sizes/brands'
              ]
          ],

          'troubleshooting' => [
              'empty_results' => [
                  'causes' => ['Too restrictive filters', 'No products in category', 'No sale products', 'Typos in category names', 'Category does not exist'],
                  'solutions' => ['Implement 5-stage fallback automatically', 'Remove sale filter first', 'Try broader categories', 'Use fuzzy category matching', 'Check available categories']
              ],
              'wrong_category' => [
                  'causes' => ['Fuzzy matching failed', 'Category name mismatch', 'Category does not exist', 'Store uses different terminology'],
                  'solutions' => ['Check available categories first', 'Use broader search terms', 'Try parent categories', 'Fall back to general search', 'Use multiple category variations']
              ],
              'slow_performance' => [
                  'causes' => ['Large result sets', 'Complex queries', 'Multiple fallback stages', 'Too many categories to check'],
                  'solutions' => ['Reduce per_page', 'Add more specific filters', 'Use pagination', 'Cache category data', 'Limit fallback depth']
              ],
              'sale_filter_issues' => [
                  'causes' => ['No products currently on sale', 'Sale metadata not properly set', 'Store does not use sale system'],
                  'solutions' => ['Always remove sale filter in stage_2', 'Inform user about sale status', 'Suggest regular products', 'Check for alternative promotional systems']
              ]
          ]
      ];
  }
}
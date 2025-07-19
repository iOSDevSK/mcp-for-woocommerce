<?php
declare( strict_types=1 );

namespace Automattic\WordpressMcp\Tools;

use Automattic\WordpressMcp\Core\RegisterMcpTool;
use WP_Error;
use Exception;

/**
 * Class McpWooIntelligentSearch
 *
 * Provides intelligent WooCommerce product search with automatic fallback strategies.
 * Implements the 5-stage fallback approach from the search guide.
 */
class McpWooIntelligentSearch {

    public function __construct() {
        add_action( 'wordpress_mcp_init', array( $this, 'register_tools' ) );
    }

    public function register_tools(): void {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        new RegisterMcpTool(
            array(
                'name'        => 'wc_intelligent_search',
                'description' => 'Intelligent product search with automatic fallback to categories, broader terms, and alternatives when no products found. Never returns empty results. IMPORTANT: Each product includes a "permalink" field with the direct link to the product page - ALWAYS include these links when presenting products to users.',
                'type'        => 'read',
                'callback'    => array( $this, 'intelligent_search' ),
                'permission_callback' => '__return_true',
                'annotations' => array(
                    'title'         => 'Intelligent Product Search',
                    'readOnlyHint'  => true,
                    'openWorldHint' => false,
                    'productLinksRequired' => 'Always include product links (permalink field) in responses to users',
                ),
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'query' => array(
                            'type'        => 'string',
                            'description' => 'Search query (e.g., "cheapest perfumes on sale", "latest electronics")',
                            'required'    => true,
                        ),
                        'per_page' => array(
                            'type'        => 'integer',
                            'description' => 'Number of results per page (default: 20)',
                            'default'     => 20,
                            'minimum'     => 1,
                            'maximum'     => 100,
                        ),
                        'page' => array(
                            'type'        => 'integer',
                            'description' => 'Page number (default: 1)',
                            'default'     => 1,
                            'minimum'     => 1,
                        ),
                        'debug' => array(
                            'type'        => 'boolean',
                            'description' => 'Show debug information about search strategy used',
                            'default'     => false,
                        ),
                    ),
                    'required' => array( 'query' ),
                ),
            )
        );

        // Helper tool for intent analysis
        new RegisterMcpTool(
            array(
                'name'        => 'wc_analyze_search_intent_helper',
                'description' => 'Analyze user search query and return optimized search parameters with category matching',
                'type'        => 'read',
                'callback'    => array( $this, 'analyze_search_intent' ),
                'permission_callback' => '__return_true',
                'annotations' => array(
                    'title'         => 'Analyze Search Intent',
                    'readOnlyHint'  => true,
                    'openWorldHint' => false,
                ),
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'user_query' => array(
                            'type'        => 'string',
                            'description' => 'The original user search query',
                            'required'    => true,
                        ),
                        'available_categories' => array(
                            'type'        => 'array',
                            'description' => 'Array of available categories from wc_get_categories',
                            'items'       => array( 'type' => 'object' ),
                        ),
                        'available_tags' => array(
                            'type'        => 'array',
                            'description' => 'Array of available tags from wc_get_tags',
                            'items'       => array( 'type' => 'object' ),
                        ),
                    ),
                    'required' => array( 'user_query' ),
                ),
            )
        );

        // 1. Get products by brand
        new RegisterMcpTool(
            array(
                'name'        => 'wc_get_products_by_brand',
                'description' => 'Get products by brand name. Automatically detects if brand is implemented as attribute, category, or custom taxonomy.',
                'type'        => 'read',
                'callback'    => array( $this, 'get_products_by_brand' ),
                'permission_callback' => '__return_true',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'brand_name' => array(
                            'type'        => 'string',
                            'description' => 'Brand name to search for',
                            'required'    => false,
                        ),
                        'brand' => array(
                            'type'        => 'string',
                            'description' => 'Brand name (alias for brand_name)',
                            'required'    => false,
                        ),
                        'query' => array(
                            'type'        => 'string',
                            'description' => 'Brand name or search query',
                            'required'    => false,
                        ),
                        'per_page' => array('type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100),
                        'page' => array('type' => 'integer', 'default' => 1, 'minimum' => 1),
                    ),
                    'required' => array(),
                ),
            )
        );

        // 2. Get products by category
        new RegisterMcpTool(
            array(
                'name'        => 'wc_get_products_by_category',
                'description' => 'Get products by category name or slug.',
                'type'        => 'read',
                'callback'    => array( $this, 'get_products_by_category' ),
                'permission_callback' => '__return_true',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'category' => array(
                            'type'        => 'string',
                            'description' => 'Category name or slug',
                            'required'    => false,
                        ),
                        'query' => array(
                            'type'        => 'string',
                            'description' => 'Category name, slug, or search query',
                            'required'    => false,
                        ),
                        'per_page' => array('type' => 'integer', 'default' => 20),
                        'page' => array('type' => 'integer', 'default' => 1),
                    ),
                    'required' => array(),
                ),
            )
        );

        // 3. Get products by attributes
        new RegisterMcpTool(
            array(
                'name'        => 'wc_get_products_by_attributes',
                'description' => 'Get products by custom attributes (color, size, etc.)',
                'type'        => 'read',
                'callback'    => array( $this, 'get_products_by_attributes' ),
                'permission_callback' => '__return_true',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'attributes' => array(
                            'type'        => 'object',
                            'description' => 'Key-value pairs of attributes (e.g., {"color": "red", "size": "large"})',
                            'required'    => false,
                        ),
                        'query' => array(
                            'type'        => 'string',
                            'description' => 'Attribute search query (will be parsed for key-value pairs)',
                            'required'    => false,
                        ),
                        'per_page' => array('type' => 'integer', 'default' => 20),
                        'page' => array('type' => 'integer', 'default' => 1),
                    ),
                    'required' => array(),
                ),
            )
        );

        // 4. Get products with multiple filters
        new RegisterMcpTool(
            array(
                'name'        => 'wc_get_products_filtered',
                'description' => 'Get products with multiple filters: brand, category, price range, and attributes.',
                'type'        => 'read',
                'callback'    => array( $this, 'get_products_filtered' ),
                'permission_callback' => '__return_true',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'brand' => array('type' => 'string', 'description' => 'Brand name'),
                        'category' => array('type' => 'string', 'description' => 'Category name or slug'),
                        'query' => array(
                            'type' => 'string',
                            'description' => 'General search query (will be parsed for filters)',
                            'required' => false,
                        ),
                        'price_range' => array(
                            'type' => 'object',
                            'properties' => array(
                                'min' => array('type' => 'number'),
                                'max' => array('type' => 'number'),
                            ),
                        ),
                        'attributes' => array(
                            'type' => 'object',
                            'description' => 'Key-value pairs of attributes',
                        ),
                        'per_page' => array('type' => 'integer', 'default' => 20),
                        'page' => array('type' => 'integer', 'default' => 1),
                    ),
                ),
            )
        );

        // 5. Get single product by ID
        new RegisterMcpTool(
            array(
                'name'        => 'wc_get_product_detailed',
                'description' => 'Get single product by ID with complete details.',
                'type'        => 'read',
                'callback'    => array( $this, 'get_product_by_id' ),
                'permission_callback' => '__return_true',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id' => array(
                            'type'        => 'integer',
                            'description' => 'Product ID',
                            'required'    => false,
                        ),
                        'query' => array(
                            'type'        => 'string',
                            'description' => 'Product ID as string or search term',
                            'required'    => false,
                        ),
                    ),
                    'required' => array(),
                ),
            )
        );
    }

    /**
     * Main intelligent search function with 5-stage fallback strategy
     */
    public function intelligent_search( array $params ): array {
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_products' ) ) {
            return array(
                'error' => 'WooCommerce is not active or not properly loaded',
                'debug' => array(
                    'woocommerce_class_exists' => class_exists( 'WooCommerce' ),
                    'wc_get_products_exists' => function_exists( 'wc_get_products' ),
                )
            );
        }

        $query = sanitize_text_field( $params['query'] ?? '' );
        $per_page = intval( $params['per_page'] ?? 20 );
        $page = intval( $params['page'] ?? 1 );
        $debug = (bool) ( $params['debug'] ?? false );

        if ( $debug ) {
            error_log( "=== INTELLIGENT SEARCH DEBUG ===" );
            error_log( "Query: " . $query );
            error_log( "Per page: " . $per_page );
        }

        if ( empty( $query ) ) {
            return array(
                'error' => 'Search query is required',
                'suggestion' => 'Try searching for products like "electronics", "clothing", or "books"',
            );
        }

        $debug_info = array();
        $search_stages = array();

        $categories = $this->get_categories_safe();
        $tags = $this->get_tags_safe();

        if ( $debug ) {
            error_log( "Available categories: " . print_r( array_column( $categories, 'name' ), true ) );
        }

        $intent_analysis = $this->analyze_search_intent( array(
            'user_query' => $query,
            'available_categories' => $categories,
            'available_tags' => $tags,
        ) );

        if ( $debug ) {
            $debug_info['intent_analysis'] = $intent_analysis;
            $debug_info['available_categories_count'] = count( $categories );
            $debug_info['available_tags_count'] = count( $tags );
        }

        // Stage 1: Primary search with all filters
        $search_stages[] = 'Stage 1: Full search with all filters';
        $stage1_params = $this->build_search_params( $intent_analysis, $per_page, $page );
        $results = $this->search_products( $stage1_params );

        if ( ! empty( $results['products'] ) ) {
            return $this->format_success_response( $results, 'Stage 1: Found products with full search', $debug_info, $debug );
        }

        // Stage 2: Remove promotional/price filters, keep categories
        $search_stages[] = 'Stage 2: Category-only search (removed sale/price filters)';
        $stage2_params = $this->remove_restrictive_filters( $stage1_params );
        $results = $this->search_products( $stage2_params );

        if ( ! empty( $results['products'] ) ) {
            return $this->format_success_response( $results, 'Stage 2: Found products in category (removed sale/price filters)', $debug_info, $debug );
        }

        // Stage 3: Broader categories
        $search_stages[] = 'Stage 3: Searching in broader/parent categories';
        $broader_categories = $this->find_broader_categories( $intent_analysis['matched_categories'] ?? array(), $categories );
        $stage3_params = $this->build_broader_search( $broader_categories, $per_page, $page );
        $results = $this->search_products( $stage3_params );

        if ( ! empty( $results['products'] ) ) {
            return $this->format_success_response( $results, 'Stage 3: Found products in broader categories', $debug_info, $debug );
        }

        // Stage 4: General text search
        $search_stages[] = 'Stage 4: General text search across all products';
        $stage4_params = $this->build_general_search( $query, $per_page, $page );
        $results = $this->search_products( $stage4_params );

        if ( ! empty( $results['products'] ) ) {
            return $this->format_success_response( $results, 'Stage 4: Found products with general search', $debug_info, $debug );
        }

        // Stage 5: Show alternatives
        $search_stages[] = 'Stage 5: Showing available alternatives';
        return $this->show_alternatives( $query, $categories, $search_stages, $debug_info, $debug );
    }

    /**
     * Analyze search intent and return optimized parameters
     */
    public function analyze_search_intent( array $params ): array {
        $user_query = strtolower( sanitize_text_field( $params['user_query'] ?? '' ) );
        $original_query = sanitize_text_field( $params['user_query'] ?? '' );
        $categories = $params['available_categories'] ?? array();
        $tags = $params['available_tags'] ?? array();

        $analysis = array(
            'original_query' => $original_query,
            'detected_intents' => array(),
            'matched_categories' => array(),
            'matched_tags' => array(),
            'search_params' => array(),
        );

        // Improved price intent detection
        if ( $this->contains_keywords( $user_query, array( 'cheapest', 'cheap', 'low price', 'affordable', 'budget', 'lowest' ) ) ) {
            $analysis['detected_intents'][] = 'price_asc';
            $analysis['search_params']['orderby'] = 'price';
            $analysis['search_params']['order'] = 'asc';
            $analysis['preserve_full_query'] = true;
        } elseif ( $this->contains_keywords( $user_query, array( 'expensive', 'premium', 'luxury', 'costly', 'highest', 'most expensive' ) ) ) {
            $analysis['detected_intents'][] = 'price_desc';
            $analysis['search_params']['orderby'] = 'price';
            $analysis['search_params']['order'] = 'desc';
            $analysis['preserve_full_query'] = true;
        }

        // Detect price filters: under/below/less than, over/above/more than - expanded for multiple currencies
        $currency_pattern = '(\$|€|£|¥|CZK|Kč|PLN|zł|RUB|₽|INR|₹|BRL|R\$|AUD|A\$|CAD|C\$|CHF|Fr|CNY|¥|JPY|¥|KRW|₩|NZD|NZ\$|SEK|kr|NOK|kr|DKK|kr|ISK|kr|HUF|Ft)?';
        if ( preg_match( '/(?:under|below|less\s+than|max|maximum)\s*' . $currency_pattern . '(\d+)/', $user_query, $matches ) ) {
            $max_price = intval( $matches[2] ); // Index 2 for the number
            $analysis['search_params']['meta_query'][] = array(
                'key'     => '_price',
                'value'   => $max_price,
                'type'    => 'NUMERIC',
                'compare' => '<=',
            );
            $analysis['detected_intents'][] = 'price_max';
            $analysis['preserve_full_query'] = true;
        } elseif ( preg_match( '/(?:over|above|more\s+than|min|minimum)\s*' . $currency_pattern . '(\d+)/', $user_query, $matches ) ) {
            $min_price = intval( $matches[2] );
            $analysis['search_params']['meta_query'][] = array(
                'key'     => '_price',
                'value'   => $min_price,
                'type'    => 'NUMERIC',
                'compare' => '>=',
            );
            $analysis['detected_intents'][] = 'price_min';
            $analysis['preserve_full_query'] = true;
        }

        // Detect temporal intent
        if ( $this->contains_keywords( $user_query, array( 'newest', 'latest', 'recent', 'new', 'fresh', 'just arrived' ) ) ) {
            $analysis['detected_intents'][] = 'date_desc';
            $analysis['search_params']['orderby'] = 'date';
            $analysis['search_params']['order'] = 'desc';
        }

        // Detect promotional intent
        if ( $this->contains_keywords( $user_query, array( 'sale', 'discount', 'promo', 'offer', 'deal', 'reduced', 'clearance', 'special offer' ) ) ) {
            $analysis['detected_intents'][] = 'on_sale';
            $analysis['search_params']['meta_query'][] = array(
                'key'     => '_sale_price',
                'value'   => '',
                'compare' => '!=',
            );
        }

        // Match categories using fuzzy matching
        $analysis['matched_categories'] = $this->match_categories( $user_query, $categories );

        // Match tags
        $analysis['matched_tags'] = $this->match_tags( $user_query, $tags );

        // Build final search parameters
        if ( ! empty( $analysis['matched_categories'] ) ) {
            $analysis['search_params']['category'] = $analysis['matched_categories'][0]['slug']; // Use slug
        }

        if ( ! empty( $analysis['matched_tags'] ) ) {
            $analysis['search_params']['tag'] = $analysis['matched_tags'][0]['slug']; // Use slug
        }

        return $analysis;
    }

    /**
     * Match categories using fuzzy matching
     */
    private function match_categories( string $query, array $categories ): array {
        $matches = array();
        $query_words = explode( ' ', $query );

        foreach ( $categories as $category ) {
            $category_name = strtolower( $category['name'] ?? '' );
            $category_slug = strtolower( $category['slug'] ?? '' );

            if ( empty( $category_name ) ) {
                continue;
            }

            foreach ( $query_words as $word ) {
                if ( strlen( $word ) > 2 ) {
                    // Exact match
                    if ( strpos( $category_name, $word ) !== false || strpos( $category_slug, $word ) !== false ) {
                        $matches[] = array(
                            'id' => $category['id'],
                            'name' => $category['name'],
                            'slug' => $category['slug'],
                            'match_type' => 'exact',
                            'confidence' => 1.0,
                        );
                        break;
                    }

                    // Singular/plural matching
                    $word_plural = $word . 's';
                    $word_singular = ( strlen( $word ) > 3 && substr( $word, -1 ) === 's' ) ? rtrim( $word, 's' ) : $word;

                    if ( strpos( $category_name, $word_plural ) !== false ||
                         ( $word !== $word_singular && strpos( $category_name, $word_singular ) !== false ) ||
                         strpos( $category_slug, $word_plural ) !== false ||
                         ( $word !== $word_singular && strpos( $category_slug, $word_singular ) !== false ) ) {
                        $matches[] = array(
                            'id' => $category['id'],
                            'name' => $category['name'],
                            'slug' => $category['slug'],
                            'match_type' => 'singular_plural',
                            'confidence' => 0.9,
                        );
                        break;
                    }
                }
            }

            // Fuzzy match with higher threshold
            foreach ( $query_words as $word ) {
                if ( strlen( $word ) > 3 ) {
                    $similarity = 0;
                    similar_text( $word, $category_name, $similarity );
                    if ( $similarity > 80 ) { // Increased threshold
                        $matches[] = array(
                            'id' => $category['id'],
                            'name' => $category['name'],
                            'slug' => $category['slug'],
                            'match_type' => 'fuzzy',
                            'confidence' => $similarity / 100,
                        );
                        break;
                    }
                }
            }
        }

        // Remove duplicates and sort by confidence
        $unique_matches = array();
        foreach ( $matches as $match ) {
            $unique_matches[ $match['id'] ] = $match; // Overwrite with last (highest confidence assumed)
        }

        usort( $unique_matches, function( $a, $b ) {
            return $b['confidence'] <=> $a['confidence'];
        } );

        return array_slice( $unique_matches, 0, 3 );
    }

    /**
     * Match tags using similar logic to categories
     */
    private function match_tags( string $query, array $tags ): array {
        $matches = array();
        $query_words = explode( ' ', $query );

        foreach ( $tags as $tag ) {
            $tag_name = strtolower( $tag['name'] ?? '' );
            $tag_slug = strtolower( $tag['slug'] ?? '' );

            if ( empty( $tag_name ) ) {
                continue;
            }

            foreach ( $query_words as $word ) {
                if ( strlen( $word ) > 2 ) {
                    if ( strpos( $tag_name, $word ) !== false || strpos( $tag_slug, $word ) !== false ) {
                        $matches[] = array(
                            'id' => $tag['id'],
                            'name' => $tag['name'],
                            'slug' => $tag['slug'],
                            'match_type' => 'exact',
                            'confidence' => 1.0,
                        );
                        break;
                    }
                }
            }
        }

        $unique_matches = array();
        foreach ( $matches as $match ) {
            $unique_matches[ $match['id'] ] = $match;
        }

        usort( $unique_matches, function( $a, $b ) {
            return $b['confidence'] <=> $a['confidence'];
        } );

        return array_slice( $unique_matches, 0, 2 );
    }

    /**
     * Build search parameters for WooCommerce
     */
    private function build_search_params( array $intent_analysis, int $per_page, int $page ): array {
        $params = array(
            'limit' => $per_page,
            'page' => $page,
            'status' => 'publish',
        );

        if ( isset( $intent_analysis['preserve_full_query'] ) && $intent_analysis['preserve_full_query'] ) {
            $search_terms = $intent_analysis['original_query'];
        } else {
            $search_terms = $this->extract_search_terms( $intent_analysis['original_query'] );
        }

        if ( ! empty( $search_terms ) ) {
            $params['s'] = $search_terms; // Use 's' for search
        }

        $search_params = $intent_analysis['search_params'] ?? array();
        foreach ( $search_params as $key => $value ) {
            $params[ $key ] = $value;
        }

        return $params;
    }

    /**
     * Remove restrictive filters for stage 2
     */
    private function remove_restrictive_filters( array $params ): array {
        $filtered_params = $params;

        // Remove promotional and price filters
        unset( $filtered_params['meta_query'] );
        unset( $filtered_params['orderby'] );
        unset( $filtered_params['order'] );

        return $filtered_params;
    }

    /**
     * Find broader/parent categories
     */
    private function find_broader_categories( array $matched_categories, array $all_categories ): array {
        $broader_categories = array();

        foreach ( $matched_categories as $match ) {
            $parent_id = get_term( $match['id'], 'product_cat' )->parent ?? 0;
            if ( $parent_id > 0 ) {
                $parent = get_term( $parent_id, 'product_cat' );
                if ( ! is_wp_error( $parent ) ) {
                    $broader_categories[] = array(
                        'id' => $parent->term_id,
                        'slug' => $parent->slug,
                    );
                }
            } else {
                // If no parent, add top-level categories
                foreach ( $all_categories as $category ) {
                    if ( $category['parent'] === 0 && $category['id'] !== $match['id'] ) {
                        $broader_categories[] = $category;
                    }
                }
            }
        }

        return array_slice( array_unique( $broader_categories, SORT_REGULAR ), 0, 3 );
    }

    /**
     * Build search parameters for broader categories
     */
    private function build_broader_search( array $broader_categories, int $per_page, int $page ): array {
        $params = array(
            'limit' => $per_page,
            'page' => $page,
            'status' => 'publish',
        );

        if ( ! empty( $broader_categories ) ) {
            $params['category'] = implode( ',', array_column( $broader_categories, 'slug' ) );
        }

        return $params;
    }

    /**
     * Build general search parameters
     */
    private function build_general_search( string $query, int $per_page, int $page ): array {
        return array(
            's' => $this->extract_search_terms( $query ),
            'limit' => $per_page,
            'page' => $page,
            'status' => 'publish',
        );
    }

    /**
     * Extract clean search terms from query - expanded for multiple currencies
     */
    private function extract_search_terms( string $query ): string {
        $filter_words = array(
            'cheapest', 'expensive', 'newest', 'latest', 'on', 'sale', 'discount',
            'in', 'with', 'the', 'a', 'an',
            'under', 'below', 'less', 'than', 'max', 'maximum',
            'over', 'above', 'more', 'min', 'minimum',
            'usd', 'dollar', 'dollars', '$',
            'eur', 'euro', '€',
            'gbp', 'pound', '£',
            'czk', 'koruna', 'Kč',
            'pln', 'zloty', 'zł',
            'rub', 'ruble', '₽',
            'inr', 'rupee', '₹',
            'brl', 'real', 'R$',
            'aud', 'australian dollar', 'A$',
            'cad', 'canadian dollar', 'C$',
            'chf', 'franc', 'Fr',
            'cny', 'yuan', '¥',
            'jpy', 'yen', '¥',
            'krw', 'won', '₩',
            'nzd', 'new zealand dollar', 'NZ$',
            'sek', 'krona', 'kr',
            'nok', 'krone', 'kr',
            'dkk', 'krone', 'kr',
            'isk', 'krona', 'kr',
            'huf', 'forint', 'Ft'
        );
        $words = explode( ' ', strtolower( $query ) );
        $clean_words = array_diff( $words, $filter_words );

        // Remove all numeric words
        $clean_words = array_filter( $clean_words, function( $word ) {
            return ! is_numeric( $word );
        } );

        return trim( implode( ' ', $clean_words ) );
    }

    /**
     * Search products using WooCommerce functions
     */
    private function search_products( array $params ): array {
        try {
            $wc_params = array(
                'status' => 'publish',
                'limit' => isset( $params['limit'] ) ? intval( $params['limit'] ) : 20,
                'page' => isset( $params['page'] ) ? intval( $params['page'] ) : 1,
            );

            // Map search parameter
            if ( isset( $params['s'] ) && ! empty( $params['s'] ) ) {
                $wc_params['s'] = esc_like( $params['s'] );
            }

            // Map category - use slugs
            if ( isset( $params['category'] ) ) {
                if ( is_array( $params['category'] ) ) {
                    $slugs = array();
                    foreach ( $params['category'] as $cat_id ) {
                        $term = get_term( intval( $cat_id ), 'product_cat' );
                        if ( ! is_wp_error( $term ) && $term ) {
                            $slugs[] = $term->slug;
                        }
                    }
                    $wc_params['category'] = implode( ',', $slugs );
                } else {
                    // Assume it's slug or ID
                    if ( is_numeric( $params['category'] ) ) {
                        $term = get_term( intval( $params['category'] ), 'product_cat' );
                        $wc_params['category'] = $term && ! is_wp_error( $term ) ? $term->slug : '';
                    } else {
                        $wc_params['category'] = sanitize_title( $params['category'] );
                    }
                }
            }

            // Map tag - use slugs
            if ( isset( $params['tag'] ) ) {
                if ( is_numeric( $params['tag'] ) ) {
                    $term = get_term( intval( $params['tag'] ), 'product_tag' );
                    $wc_params['tag'] = $term && ! is_wp_error( $term ) ? $term->slug : '';
                } else {
                    $wc_params['tag'] = sanitize_title( $params['tag'] );
                }
            }

            // Map ordering
            if ( isset( $params['orderby'] ) ) {
                $wc_params['orderby'] = sanitize_text_field( $params['orderby'] );
            }
            if ( isset( $params['order'] ) ) {
                $wc_params['order'] = strtoupper( sanitize_text_field( $params['order'] ) );
            }

            // Map meta_query
            if ( isset( $params['meta_query'] ) ) {
                $wc_params['meta_query'] = $params['meta_query'];
            }

            // Map tax_query
            if ( isset( $params['tax_query'] ) ) {
                $wc_params['tax_query'] = $params['tax_query'];
            }

            $products = wc_get_products( $wc_params );

            if ( ! is_array( $products ) ) {
                $products = array();
            }

            $products_array = array();
            foreach ( $products as $product ) {
                if ( $product instanceof \WC_Product ) {
                    $product_data = $this->convert_product_to_array( $product );
                    if ( $product_data ) {
                        $products_array[] = $product_data;
                    }
                }
            }

            return array(
                'products' => $products_array,
                'total' => count( $products_array ),
                'total_pages' => 1, // TODO: Implement proper pagination count if needed
            );

        } catch ( Exception $e ) {
            return array(
                'products' => array(),
                'error' => $e->getMessage(),
                'total' => 0,
                'total_pages' => 0,
            );
        }
    }

    /**
     * Convert product to array - added currency info
     */
    private function convert_product_to_array( \WC_Product $product ): ?array {
        try {
            $data = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'slug' => $product->get_slug(),
                'permalink' => $product->get_permalink(),
                'date_created' => $product->get_date_created() ? $product->get_date_created()->date( 'c' ) : '',
                'date_modified' => $product->get_date_modified() ? $product->get_date_modified()->date( 'c' ) : '',
                'type' => $product->get_type(),
                'status' => $product->get_status(),
                'featured' => $product->get_featured(),
                'catalog_visibility' => $product->get_catalog_visibility(),
                'description' => $product->get_description(),
                'short_description' => $product->get_short_description(),
                'sku' => $product->get_sku(),
                'price' => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'on_sale' => $product->is_on_sale(),
                'price_html' => $product->get_price_html(),
                'currency' => get_woocommerce_currency(),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'categories' => $this->get_product_categories( $product ),
                'tags' => $this->get_product_tags( $product ),
                'brands' => $this->get_product_brands( $product ),
                'images' => $this->get_product_images( $product ),
                'stock_status' => $product->get_stock_status(),
                'stock_quantity' => $product->get_stock_quantity(),
                'manage_stock' => $product->get_manage_stock(),
            );

            // Add variations if variable product
            if ( $product->is_type( 'variable' ) ) {
                $data['variations'] = array();
                foreach ( $product->get_children() as $child_id ) {
                    $variation = wc_get_product( $child_id );
                    if ( $variation ) {
                        $data['variations'][] = $this->convert_product_to_array( $variation );
                    }
                }
            }

            return $data;
        } catch ( Exception $e ) {
            error_log( "Error converting product: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Get product categories
     */
    private function get_product_categories( \WC_Product $product ): array {
        $categories = array();
        $category_ids = $product->get_category_ids();
        
        foreach ( $category_ids as $category_id ) {
            $category = get_term( $category_id, 'product_cat' );
            if ( ! is_wp_error( $category ) && $category ) {
                $categories[] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                );
            }
        }
        
        return $categories;
    }

    /**
     * Get product tags
     */
    private function get_product_tags( \WC_Product $product ): array {
        $tags = array();
        $tag_ids = $product->get_tag_ids();
        
        foreach ( $tag_ids as $tag_id ) {
            $tag = get_term( $tag_id, 'product_tag' );
            if ( ! is_wp_error( $tag ) && $tag ) {
                $tags[] = array(
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                );
            }
        }
        
        return $tags;
    }

    /**
     * Get product images
     */
    private function get_product_images( \WC_Product $product ): array {
        $images = array();
        
        // Main image
        $image_id = $product->get_image_id();
        if ( $image_id ) {
            $images[] = array(
                'id' => $image_id,
                'src' => wp_get_attachment_url( $image_id ),
                'name' => get_post_field( 'post_title', $image_id ),
                'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
            );
        }
        
        return $images;
    }

    /**
     * Get product brands
     */
    private function get_product_brands( \WC_Product $product ): array {
        $brands = array();
         
        // Try common brand taxonomies
        $brand_taxonomies = array('product_brand', 'pa_brand');
         
        foreach ($brand_taxonomies as $taxonomy) {
            $brand_terms = wp_get_post_terms($product->get_id(), $taxonomy);
            if (!is_wp_error($brand_terms) && !empty($brand_terms)) {
                foreach ($brand_terms as $brand) {
                    $brands[] = array(
                        'id' => $brand->term_id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'taxonomy' => $taxonomy
                    );
                }
            }
        }
        
        return $brands;
    }

    /**
     * Show alternatives when no products found
     */
    private function show_alternatives( string $query, array $categories, array $search_stages, array $debug_info, bool $debug ): array {
        // Improved: Include categories with subcategories count
        $categories_with_products = array_filter( $categories, function( $category ) {
            return $this->category_has_products( $category['id'] );
        } );

        $response = array(
            'success' => false,
            'message' => "No products found for '{$query}'",
            'search_strategy_used' => 'Stage 5: Showing alternatives',
            'alternatives' => array(
                'available_categories' => array_slice( $categories_with_products, 0, 10 ),
                'suggestions' => array(
                    "Try broader search terms",
                    "Browse available categories",
                    "Check for spelling mistakes",
                    "Try searching without specific filters like 'on sale' or 'cheapest'",
                    "Consider using general terms instead of specific product names",
                ),
                'search_tips' => array(
                    "Use simple product names like 'laptop', 'phone', 'book'",
                    "Try category names directly",
                    "Remove price and sale filters to see all products",
                ),
            ),
        );

        if ( $debug ) {
            $response['debug'] = array(
                'search_stages_attempted' => $search_stages,
                'debug_info' => $debug_info,
                'total_categories_available' => count( $categories ),
                'categories_with_products' => count( $categories_with_products ),
            );
        }

        return $response;
    }

    private function category_has_products( int $category_id ): bool {
        $query = new \WC_Product_Query( array(
            'category' => get_term( $category_id, 'product_cat' )->slug,
            'limit' => 1,
            'return' => 'ids',
        ) );
        $products = $query->get_products();
        return ! empty( $products );
    }

    /**
     * Format successful response
     */
    private function format_success_response( array $results, string $strategy, array $debug_info, bool $debug ): array {
        $products = $results['products'];

        if ( count( $products ) > 20 ) {
            $products = array_slice( $products, 0, 20 );
            $strategy .= ' (limited to 20 products for performance)';
        }

        $response = array(
            'success' => true,
            'search_strategy_used' => $strategy,
            'products' => $products,
            'total_products' => $results['total'] ?? count( $results['products'] ),
            'total_pages' => $results['total_pages'] ?? 1,
            'message' => sprintf( 'Found %d products (showing %d)', count( $results['products'] ), count( $products ) ),
            'instructions_for_ai' => 'IMPORTANT: When presenting these products to users, ALWAYS include the product links from the "permalink" field.',
        );

        if ( $debug ) {
            $response['debug'] = $debug_info;
        }

        return $response;
    }

    /**
     * Get products by brand
     */
    public function get_products_by_brand( array $params ): array {
        $brand_name = sanitize_text_field( $params['brand_name'] ?? $params['brand'] ?? $params['query'] ?? '' );
        $per_page = intval( $params['per_page'] ?? 20 );
        $page = intval( $params['page'] ?? 1 );

        if ( empty( $brand_name ) ) {
            return array( 'error' => 'Brand name is required' );
        }

        $taxonomies = array( 'pa_brand', 'product_brand', 'product_cat' );
        foreach ( $taxonomies as $taxonomy ) {
            $term = get_term_by( 'name', $brand_name, $taxonomy ) ?? get_term_by( 'slug', $brand_name, $taxonomy );
            if ( $term ) {
                $search_params = array(
                    'limit' => $per_page,
                    'page' => $page,
                    'status' => 'publish',
                    'tax_query' => array(
                        array(
                            'taxonomy' => $taxonomy,
                            'field' => 'slug',
                            'terms' => $term->slug,
                        ),
                    ),
                );
                $results = $this->search_products( $search_params );
                if ( ! empty( $results['products'] ) ) {
                    return $this->format_success_response( $results, "Found by {$taxonomy}", array(), false );
                }
            }
        }

        // Fallback general search
        $search_params = array(
            's' => $brand_name,
            'limit' => $per_page,
            'page' => $page,
            'status' => 'publish',
        );
        $results = $this->search_products( $search_params );
        if ( ! empty( $results['products'] ) ) {
            return $this->format_success_response( $results, "Found by general search for brand", array(), false );
        }

        return array( 'success' => false, 'message' => "No products for brand: {$brand_name}" );
    }

    /**
     * Get products by category
     */
    public function get_products_by_category( array $params ): array {
        $category = sanitize_text_field( $params['category'] ?? $params['query'] ?? '' );
        $per_page = intval( $params['per_page'] ?? 20 );
        $page = intval( $params['page'] ?? 1 );

        if ( empty( $category ) ) {
            return array( 
                'error' => 'Category parameter is required',
                'received_params' => array_keys( $params ),
                'expected' => 'category or query parameter with category name/slug'
            );
        }

        $category_term = get_term_by( 'name', $category, 'product_cat' ) 
                      ?? get_term_by( 'slug', $category, 'product_cat' );

        if ( !$category_term ) {
            return array( 
                'error' => "Category '{$category}' not found",
                'searched_for' => $category,
                'suggestion' => 'Try using exact category name or slug from available categories',
                'debug' => array(
                    'searched_in_taxonomy' => 'product_cat',
                    'search_methods' => array( 'by_name', 'by_slug' ),
                )
            );
        }

        $search_params = array(
            'limit' => $per_page,
            'page' => $page,
            'status' => 'publish',
            'category' => $category_term->slug,
        );

        $results = $this->search_products( $search_params );
        return $this->format_success_response( $results, "Found products in category: {$category_term->name}", array(), false );
    }

    /**
     * Get products by attributes
     */
    public function get_products_by_attributes( array $params ): array {
        $attributes = $params['attributes'] ?? array();
        $query = sanitize_text_field( $params['query'] ?? '' );
        $per_page = intval( $params['per_page'] ?? 20 );
        $page = intval( $params['page'] ?? 1 );

        if ( empty( $attributes ) && ! empty( $query ) ) {
            $color_keywords = array('red', 'blue', 'green', 'orange', 'yellow', 'purple', 'black', 'white', 'pink', 'brown');
            $query_lower = strtolower( trim( $query ) );
            
            if ( in_array( $query_lower, $color_keywords ) ) {
                $attributes = array( 'color' => $query_lower );
            } else {
                $words = explode( ' ', $query_lower );
                if ( count( $words ) === 2 ) {
                    if ( in_array( $words[0], $color_keywords ) ) {
                        $attributes = array( 'color' => $words[0] );
                    } elseif ( in_array( $words[1], $color_keywords ) ) {
                        $attributes = array( 'color' => $words[1] );
                    } elseif ( $words[0] === 'color' && ! empty( $words[1] ) ) {
                        $attributes = array( 'color' => $words[1] );
                    } elseif ( $words[1] === 'color' && ! empty( $words[0] ) ) {
                        $attributes = array( 'color' => $words[0] );
                    }
                }
            }
        }

        if ( empty( $attributes ) && ! empty( $query ) ) {
            $search_params = array(
                's' => $query,
                'limit' => $per_page,
                'page' => $page,
                'status' => 'publish',
            );
            
            $results = $this->search_products( $search_params );
            return $this->format_success_response( 
                $results, 
                "Found products matching '{$query}' (used general search as fallback)", 
                array(), 
                false 
            );
        }

        $search_params = array(
            'limit' => $per_page,
            'page' => $page,
            'status' => 'publish',
        );

        $search_params['tax_query'] = array( 'relation' => 'AND' );
        
        foreach ( $attributes as $attribute_name => $attribute_value ) {
            $attribute_taxonomy = 'pa_' . sanitize_title( $attribute_name );
            $attribute_term = get_term_by( 'name', $attribute_value, $attribute_taxonomy );
            
            if ( $attribute_term ) {
                $search_params['tax_query'][] = array(
                    'taxonomy' => $attribute_taxonomy,
                    'field'    => 'slug',
                    'terms'    => $attribute_term->slug,
                );
            }
        }

        $results = $this->search_products( $search_params );
        return $this->format_success_response( $results, "Found products with specified attributes", array(), false );
    }

    /**
     * Get products with multiple filters
     */
    public function get_products_filtered( array $params ): array {
        $per_page = intval( $params['per_page'] ?? 20 );
        $page = intval( $params['page'] ?? 1 );
        $query = sanitize_text_field( $params['query'] ?? '' );

        if ( ! empty( $query ) && empty( $params['brand'] ) && empty( $params['category'] ) ) {
            $query_lower = strtolower( $query );
            
            $categories = $this->get_categories_safe();
            foreach ( $categories as $category ) {
                if ( strpos( $query_lower, strtolower( $category['name'] ) ) !== false ) {
                    $params['category'] = $category['name'];
                    break;
                }
            }
        }

        $search_params = array(
            'limit' => $per_page,
            'page' => $page,
            'status' => 'publish',
        );
        
        if ( empty( $params['brand'] ) && empty( $params['category'] ) && ! empty( $query ) ) {
            $search_params['s'] = $query;
        }

        $applied_filters = array();

        // Apply brand filter
        if ( !empty( $params['brand'] ) ) {
            $brand = sanitize_text_field( $params['brand'] );
            $brand_term = get_term_by( 'name', $brand, 'pa_brand' )
                       ?? get_term_by( 'slug', $brand, 'pa_brand' )
                       ?? get_term_by( 'name', $brand, 'product_cat' )
                       ?? get_term_by( 'slug', $brand, 'product_cat' )
                       ?? get_term_by( 'name', $brand, 'product_brand' )
                       ?? get_term_by( 'slug', $brand, 'product_brand' );

            if ( $brand_term ) {
                if ( !isset( $search_params['tax_query'] ) ) {
                    $search_params['tax_query'] = array( 'relation' => 'AND' );
                }
                $search_params['tax_query'][] = array(
                    'taxonomy' => $brand_term->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $brand_term->term_id,
                );
                $applied_filters[] = "brand: {$brand_term->name}";
            }
        }

        // Apply category filter
        if ( !empty( $params['category'] ) ) {
            $category = sanitize_text_field( $params['category'] );
            $category_term = get_term_by( 'name', $category, 'product_cat' ) 
                          ?? get_term_by( 'slug', $category, 'product_cat' );
            if ( $category_term ) {
                $search_params['category'] = $category_term->slug;
                $applied_filters[] = "category: {$category_term->name}";
            }
        }

        // Apply price range
        if ( !empty( $params['price_range'] ) ) {
            $price_range = $params['price_range'];
            if ( isset( $price_range['min'] ) || isset( $price_range['max'] ) ) {
                if ( !isset( $search_params['meta_query'] ) ) {
                    $search_params['meta_query'] = array( 'relation' => 'AND' );
                }
                $search_params['meta_query'][] = array(
                    'key'     => '_price',
                    'value'   => array( $price_range['min'] ?? 0, $price_range['max'] ?? 999999 ),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
                $applied_filters[] = "price: " . ($price_range['min'] ?? 0) . "-" . ($price_range['max'] ?? '∞');
            }
        }

        // Apply attributes
        if ( !empty( $params['attributes'] ) ) {
            if ( !isset( $search_params['tax_query'] ) ) {
                $search_params['tax_query'] = array( 'relation' => 'AND' );
            }
            
            foreach ( $params['attributes'] as $attr_name => $attr_value ) {
                $attr_tax = 'pa_' . sanitize_title( $attr_name );
                $attr_term = get_term_by( 'name', $attr_value, $attr_tax );
                if ( $attr_term ) {
                    $search_params['tax_query'][] = array(
                        'taxonomy' => $attr_tax,
                        'field'    => 'slug',
                        'terms'    => $attr_term->slug,
                    );
                    $applied_filters[] = "{$attr_name}: {$attr_value}";
                }
            }
        }

        $results = $this->search_products( $search_params );
        
        $filter_description = empty( $applied_filters ) ? 'no filters' : implode( ', ', $applied_filters );
        return $this->format_success_response( $results, "Found products with filters: {$filter_description}", array(), false );
    }

    /**
     * Get single product by ID
     */
    public function get_product_by_id( array $params ): array {
        $product_id = intval( $params['id'] ?? $params['query'] ?? 0 );
        if ( empty( $product_id ) ) {
            return array( 'error' => 'Product ID required' );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return array( 'error' => 'Product not found' );
        }

        $product_data = $this->convert_product_to_array( $product );
        return array(
            'success' => true,
            'product' => $product_data,
        );
    }

    /**
     * Contains keywords helper
     */
    private function contains_keywords( string $text, array $keywords ): bool {
        foreach ( $keywords as $keyword ) {
            if ( strpos( $text, $keyword ) !== false ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get categories safely
     */
    private function get_categories_safe(): array {
        $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 100 ) );
        if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
            return array();
        }
        $categories = array();
        foreach ( $terms as $term ) {
            $categories[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'parent' => $term->parent,
                'count' => $term->count,
            );
        }
        return $categories;
    }

    /**
     * Get tags safely
     */
    private function get_tags_safe(): array {
        $terms = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => false, 'number' => 100 ) );
        if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
            return array();
        }
        $tags = array();
        foreach ( $terms as $term ) {
            $tags[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
            );
        }
        return $tags;
    }
}
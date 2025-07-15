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
               'description' => 'Intelligent product search with automatic fallback to categories, broader terms, and alternatives when no products found. Never returns empty results.',
               'type'        => 'read',
               'callback'    => array( $this, 'intelligent_search' ),
               'permission_callback' => '__return_true', 
               'annotations' => array(
                   'title'         => 'Intelligent Product Search',
                   'readOnlyHint'  => true,
                   'openWorldHint' => false,
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
   }

   /**
    * Main intelligent search function with 5-stage fallback strategy
    */
   public function intelligent_search( array $params ): array {
       $query = $params['query'] ?? '';
       $per_page = intval( $params['per_page'] ?? 20 );
       $page = intval( $params['page'] ?? 1 );
       $debug = (bool) ( $params['debug'] ?? false );

       if ( empty( $query ) ) {
           return array(
               'error' => 'Search query is required',
               'suggestion' => 'Try searching for products like "electronics", "clothing", or "books"',
           );
       }

       $debug_info = array();
       $search_stages = array();

       // Stage 1: Get categories and analyze intent
       $categories = $this->get_categories_safe();
       $tags = $this->get_tags_safe();
       
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
       $user_query = strtolower( $params['user_query'] ?? '' );
       $categories = $params['available_categories'] ?? array();
       $tags = $params['available_tags'] ?? array();

       $analysis = array(
           'original_query' => $params['user_query'],
           'detected_intents' => array(),
           'matched_categories' => array(),
           'matched_tags' => array(),
           'search_params' => array(),
       );

       // Detect price intent
       if ( $this->contains_keywords( $user_query, array( 'cheapest', 'cheap', 'low price', 'affordable', 'budget', 'lowest' ) ) ) {
           $analysis['detected_intents'][] = 'price_asc';
           $analysis['search_params']['orderby'] = 'price';
           $analysis['search_params']['order'] = 'asc';
       } elseif ( $this->contains_keywords( $user_query, array( 'expensive', 'premium', 'luxury', 'costly', 'highest', 'most expensive' ) ) ) {
           $analysis['detected_intents'][] = 'price_desc';
           $analysis['search_params']['orderby'] = 'price';
           $analysis['search_params']['order'] = 'desc';
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
           $analysis['search_params']['on_sale'] = true;
       }

       // Match categories using fuzzy matching
       $analysis['matched_categories'] = $this->match_categories( $user_query, $categories );

       // Match tags
       $analysis['matched_tags'] = $this->match_tags( $user_query, $tags );

       // Build final search parameters
       if ( ! empty( $analysis['matched_categories'] ) ) {
           $analysis['search_params']['category'] = $analysis['matched_categories'][0]['id'];
       }

       if ( ! empty( $analysis['matched_tags'] ) ) {
           $analysis['search_params']['tag'] = $analysis['matched_tags'][0]['id'];
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

           // Skip empty categories
           if ( empty( $category_name ) ) {
               continue;
           }

           // Exact match
           foreach ( $query_words as $word ) {
               if ( strlen( $word ) > 2 ) {
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
               }
           }

           // Fuzzy match for typos
           foreach ( $query_words as $word ) {
               if ( strlen( $word ) > 3 ) {
                   $similarity = 0;
                   similar_text( $word, $category_name, $similarity );
                   if ( $similarity > 60 ) {
                       $matches[] = array(
                           'id' => $category['id'],
                           'name' => $category['name'],
                           'slug' => $category['slug'],
                           'match_type' => 'fuzzy',
                           'confidence' => $similarity / 100,
                       );
                   }
               }
           }
       }

       // Remove duplicates and sort by confidence
       $unique_matches = array();
       foreach ( $matches as $match ) {
           if ( ! isset( $unique_matches[$match['id']] ) || $unique_matches[$match['id']]['confidence'] < $match['confidence'] ) {
               $unique_matches[$match['id']] = $match;
           }
       }

       usort( $unique_matches, function( $a, $b ) {
           return $b['confidence'] <=> $a['confidence'];
       } );

       return array_slice( $unique_matches, 0, 3 ); // Return top 3 matches
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

           // Skip empty tags
           if ( empty( $tag_name ) ) {
               continue;
           }

           // Exact match
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

       // Remove duplicates and sort by confidence
       $unique_matches = array();
       foreach ( $matches as $match ) {
           if ( ! isset( $unique_matches[$match['id']] ) || $unique_matches[$match['id']]['confidence'] < $match['confidence'] ) {
               $unique_matches[$match['id']] = $match;
           }
       }

       usort( $unique_matches, function( $a, $b ) {
           return $b['confidence'] <=> $a['confidence'];
       } );

       return array_slice( $unique_matches, 0, 2 ); // Return top 2 tag matches
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

       // Add search term
       $search_terms = $this->extract_search_terms( $intent_analysis['original_query'] );
       if ( ! empty( $search_terms ) ) {
           $params['search'] = $search_terms;
       }

       // Add detected parameters
       $search_params = $intent_analysis['search_params'] ?? array();
       foreach ( $search_params as $key => $value ) {
           $params[$key] = $value;
       }

       return $params;
   }

   /**
    * Remove restrictive filters for stage 2
    */
   private function remove_restrictive_filters( array $params ): array {
       $filtered_params = $params;
       
       // Remove promotional filters
       unset( $filtered_params['on_sale'] );
       unset( $filtered_params['meta_query'] );
       
       // Remove price sorting but keep category
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
           // Find parent categories
           foreach ( $all_categories as $category ) {
               if ( isset( $category['parent'] ) && $category['parent'] === 0 && $category['id'] !== $match['id'] ) {
                   $broader_categories[] = $category;
               }
           }
       }

       return array_slice( $broader_categories, 0, 3 ); // Limit to 3 broader categories
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
           $params['category'] = $broader_categories[0]['id'];
       }

       return $params;
   }

   /**
    * Build general search parameters
    */
   private function build_general_search( string $query, int $per_page, int $page ): array {
       return array(
           'search' => $this->extract_search_terms( $query ),
           'limit' => $per_page,
           'page' => $page,
           'status' => 'publish',
       );
   }

   /**
    * Extract clean search terms from query
    */
   private function extract_search_terms( string $query ): string {
       // Remove common filter words
       $filter_words = array( 'cheapest', 'expensive', 'newest', 'latest', 'on', 'sale', 'discount', 'in', 'with', 'the', 'a', 'an' );
       $words = explode( ' ', strtolower( $query ) );
       $clean_words = array_diff( $words, $filter_words );
       
       return trim( implode( ' ', $clean_words ) );
   }

   /**
    * Search products using WooCommerce functions
    */
   private function search_products( array $params ): array {
       try {
           // Use WooCommerce's wc_get_products function
           $products = wc_get_products( $params );
           
           // Convert product objects to arrays for consistency
           $products_array = array();
           foreach ( $products as $product ) {
               if ( $product instanceof \WC_Product ) {
                   $products_array[] = array(
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
                       'categories' => $this->get_product_categories( $product ),
                       'tags' => $this->get_product_tags( $product ),
                       'images' => $this->get_product_images( $product ),
                       'stock_status' => $product->get_stock_status(),
                       'stock_quantity' => $product->get_stock_quantity(),
                       'manage_stock' => $product->get_manage_stock(),
                   );
               }
           }

           return array(
               'products' => $products_array,
               'total' => count( $products_array ),
               'total_pages' => 1,
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
    * Show alternatives when no products found
    */
   private function show_alternatives( string $query, array $categories, array $search_stages, array $debug_info, bool $debug ): array {
       // Filter categories to show only those with products
       $categories_with_products = array_filter( $categories, function( $category ) {
           return isset( $category['count'] ) && $category['count'] > 0;
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

   /**
    * Format successful response
    */
   private function format_success_response( array $results, string $strategy, array $debug_info, bool $debug ): array {
       $response = array(
           'success' => true,
           'search_strategy_used' => $strategy,
           'products' => $results['products'],
           'total_products' => $results['total'] ?? count( $results['products'] ),
           'total_pages' => $results['total_pages'] ?? 1,
           'message' => sprintf( 'Found %d products', count( $results['products'] ) ),
       );

       if ( $debug ) {
           $response['debug'] = $debug_info;
       }

       return $response;
   }

   /**
    * Helper functions
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
    * Get categories safely using WooCommerce functions
    */
   private function get_categories_safe(): array {
       try {
           $categories = get_terms( array(
               'taxonomy' => 'product_cat',
               'hide_empty' => false,
               'number' => 100,
           ) );

           if ( is_wp_error( $categories ) ) {
               return array();
           }

           $categories_array = array();
           foreach ( $categories as $category ) {
               $categories_array[] = array(
                   'id' => $category->term_id,
                   'name' => $category->name,
                   'slug' => $category->slug,
                   'parent' => $category->parent,
                   'count' => $category->count,
                   'description' => $category->description,
               );
           }

           return $categories_array;

       } catch ( Exception $e ) {
           return array();
       }
   }

   /**
    * Get tags safely using WooCommerce functions
    */
   private function get_tags_safe(): array {
       try {
           $tags = get_terms( array(
               'taxonomy' => 'product_tag',
               'hide_empty' => false,
               'number' => 100,
           ) );

           if ( is_wp_error( $tags ) ) {
               return array();
           }

           $tags_array = array();
           foreach ( $tags as $tag ) {
               $tags_array[] = array(
                   'id' => $tag->term_id,
                   'name' => $tag->name,
                   'slug' => $tag->slug,
                   'count' => $tag->count,
                   'description' => $tag->description,
               );
           }

           return $tags_array;

       } catch ( Exception $e ) {
           return array();
       }
   }
}
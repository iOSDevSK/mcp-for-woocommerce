<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Core;

/**
 * Class WpFeaturesAdapter
 * Exposes WordPress features as MCP tools.
 *
 * @package Automattic\WordpressMcp
 */
class WpFeaturesAdapter {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wordpress_mcp_init', array( $this, 'init' ) );
	}

	/**
	 * Convert a WP REST method (or feature type) into an MCP functionality type.
	 *
	 * @param string $rest_method The HTTP method (GET, POST, PUT, PATCH, DELETE).
	 * @param string $feature_type The WP Feature type (resource|tool) used as a fallback.
	 * @return string One of create|read|update|delete.
	 */
	private function map_functionality_type( string $rest_method, string $feature_type ): string {
		$rest_method = strtoupper( $rest_method );

		$map = array(
			'GET'    => 'read',
			'HEAD'   => 'read',
			'POST'   => 'create',
			'PUT'    => 'update',
			'PATCH'  => 'update',
			'DELETE' => 'delete',
		);

		if ( isset( $map[ $rest_method ] ) ) {
			return $map[ $rest_method ];
		}

		// Fallback when no REST alias exists.
		return ( 'tool' === $feature_type ) ? 'create' : 'read';
	}

	/**
	 * Initializes the feature registry.
	 */
	public function init(): void {
		// Make sure the function exists and is loaded from global namespace.
		if ( ! function_exists( '\\wp_feature_registry' ) ) {
			return;
		}

		// Call the global function with \ prefix.
		$features = \wp_feature_registry()->get();

		foreach ( $features as $feature ) {
			$input_schema  = $feature->get_input_schema();
			$output_schema = $feature->get_output_schema();

			if ( empty( $input_schema ) && empty( $output_schema ) ) {
				continue;
			}

			// Determine MCP functionality type.
			$rest_method   = $feature->get_rest_method();
			$feature_type  = $feature->get_type();
			$mcp_type      = $this->map_functionality_type( $rest_method, $feature_type );

			$permissions_callback = null;
			if ( method_exists( $feature, 'get_permission_callback' ) ) {
				$permissions_callback = function ( $args ) use ( $feature ) {
					// Get the callback function from the feature
					$callback = $feature->get_permission_callback();
					return $callback( $args );
				};
			}

			$the_feature = array(
				'name'                 => 'wp_feature_' . sanitize_title( $feature->get_name() ),
				'description'          => $feature->get_description(),
				'type'                 => $mcp_type,
				'inputSchema'          => $input_schema,
				'outputSchema'         => $output_schema,
				'permissions_callback' => $permissions_callback,
			);

			if ( $feature->has_rest_alias() ) {
				// Check if get_rest_alias returns a string or possibly an array/object
				$rest_alias = null;
				if ( method_exists( $feature, 'get_rest_alias' ) ) {
					$rest_alias = $feature->get_rest_alias();
					
					// Handle potential WP_Error or array return
					if ( is_string( $rest_alias ) ) {
						$route = $rest_alias;
					} elseif ( is_wp_error( $rest_alias ) || is_array( $rest_alias ) ) {
						// If it's an error or complex structure, skip this feature
						continue;
					} else {
						// If it's the REST alias is stored in the feature itself
						$rest_alias_prop = $feature->rest_alias ?? null;
						$route = is_string( $rest_alias_prop ) ? $rest_alias_prop : null;
						
						if ( null === $route ) {
							continue;
						}
					}
				} else {
					// As a fallback, try accessing the property directly
					$rest_alias_prop = $feature->rest_alias ?? null;
					$route = is_string( $rest_alias_prop ) ? $rest_alias_prop : null;
					
					if ( null === $route ) {
						continue;
					}
				}
				
				$the_feature['rest_alias'] = array(
					'route'  => $route,
					'method' => $rest_method,
				);
			} else {
				// Non REST-alias features fall back to executing the feature callback directly.
				$callback = null;
				if ( method_exists( $feature, 'get_callback' ) ) {
					$callback = $feature->get_callback();
				}
				
				if ( is_callable( $callback ) ) {
					$the_feature['callback'] = function ( $args ) use ( $feature, $callback ) {
						return call_user_func( $callback, $args );
					};
				} else {
					// Skip this feature if no valid callback
					continue;
				}
			}

			new RegisterMcpTool( $the_feature );
		}
	}
}

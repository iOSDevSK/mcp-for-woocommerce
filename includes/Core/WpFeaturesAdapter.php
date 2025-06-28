<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Core;

use Automattic\WordpressMcp\Utils\InputSchema;
use stdClass;

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
			$input_schema = $feature->get_input_schema();

			if ( empty( $input_schema ) ) {
				$input_schema = array(
					'type'       => 'object',
					'properties' => new stdClass(),
					'required'   => array(),
				);
			}

			// Determine the MCP functionality type.
			$rest_method  = $feature->get_rest_method();
			$feature_type = $feature->get_type();
			$mcp_type     = $this->map_functionality_type( $rest_method, $feature_type );

			$permission_callback = '__return_true';
			if ( method_exists( $feature, 'get_permission_callback' ) ) {
				$permission_callback = function ( $args ) use ( $feature ) {
					// Get the callback function from the feature.
					$callback = $feature->get_permission_callback();
					return $callback( $args );
				};
			}

			$callback = function ( $args ) use ( $feature ) {
				// Convert array to WP_REST_Request object if needed.
				if ( is_array( $args ) ) {
					$request = new \WP_REST_Request();
					foreach ( $args as $key => $value ) {
						$request->set_param( $key, $value );
					}
					$args = $request;
				}

				// Get the callback function from the feature.
				$callback = $feature->get_callback();
				$result   = $callback( $args );

				// Convert WP_REST_Response to array if needed.
				if ( $result instanceof \WP_REST_Response ) {
					return $result->get_data();
				}

				return $result;
			};

			$the_feature = array(
				'name'                => 'wp_feature_' . sanitize_title( $feature->get_name() ),
				'description'         => $feature->get_description(),
				'type'                => $mcp_type,
				'inputSchema'         => InputSchema::clean( $input_schema ),
				'permission_callback' => $permission_callback,
				'callback'            => $callback,
			);

			new RegisterMcpTool( $the_feature );
		}
	}
}

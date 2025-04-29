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
	 * Initializes the feature registry.
	 */
	public function init(): void {
		if ( ! function_exists( 'wp_feature_registry' ) ) {
			return;
		}

		$features = wp_feature_registry()->get();

		foreach ( $features as $feature ) {
			$input_schema  = $feature->get_input_schema();
			$output_schema = $feature->get_output_schema();

			if ( empty( $input_schema ) && empty( $output_schema ) ) {
				continue;
			}

			$the_feature = array(
				'name'                 => 'wp_feature_' . sanitize_title( $feature->get_name() ),
				'description'          => $feature->get_description(),
				'inputSchema'          => $input_schema,
				'outputSchema'         => $output_schema,
				'permissions_callback' => function ( $args ) use ( $feature ) {
					return $feature->get_permissions_callback( $args );
				},
			);

			if ( $feature->has_rest_alias() ) {
				$the_feature['rest_alias']['route']  = $feature->get_the_rest_alias();
				$the_feature['rest_alias']['method'] = $feature->get_rest_method();
			} else {
				$the_feature['callback'] = function ( $args ) use ( $feature ) {
					return $feature->get_callback( $args );
				};
			}

			new RegisterMcpTool( $the_feature );
		}
	}
}

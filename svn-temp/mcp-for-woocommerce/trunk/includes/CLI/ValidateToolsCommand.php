<?php
/**
 * WP-CLI command for validating MCP tools
 *
 * @package McpForWoo
 * @subpackage CLI
 */



namespace McpForWoo\CLI;

use WP_CLI;
use McpForWoo\Utils\ToolValidator;
use McpForWoo\Utils\SchemaValidator;
use McpForWoo\Core\RegisterMcpTool;

/**
 * Validates MCP tools for compliance and functionality.
 */
class ValidateToolsCommand {

	/**
	 * Validates all registered MCP tools
	 *
	 * ## OPTIONS
	 *
	 * [--level=<level>]
	 * : Validation level: strict, extended, or permissive
	 * ---
	 * default: extended
	 * options:
	 *   - strict
	 *   - extended
	 *   - permissive
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp woo-mcp validate-tools
	 *     wp woo-mcp validate-tools --level=strict
	 *     wp woo-mcp validate-tools --format=json
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$level  = $assoc_args['level'] ?? 'extended';
		$format = $assoc_args['format'] ?? 'table';

		if ( ! in_array( $level, array( 'strict', 'extended', 'permissive' ), true ) ) {
			WP_CLI::error( 'Invalid validation level. Use: strict, extended, or permissive' );
		}

		WP_CLI::log( "Validating MCP tools with {$level} validation level..." );

		$registered_tools = $this->get_registered_tools();
		$validation_results = array();
		$schema_validator = new SchemaValidator();

		if ( empty( $registered_tools ) ) {
			WP_CLI::warning( 'No MCP tools found to validate.' );
			return;
		}

		$progress = WP_CLI\Utils\make_progress_bar( 'Validating tools', count( $registered_tools ) );

		foreach ( $registered_tools as $tool_name => $tool_data ) {
			$progress->tick();

			$tool_validator = new ToolValidator( array(), $level );
			$validation_result = $tool_validator->validate( $tool_data );

			$result_data = array(
				'tool'   => $tool_name,
				'status' => is_wp_error( $validation_result ) ? 'failed' : 'passed',
				'errors' => array(),
			);

			if ( is_wp_error( $validation_result ) ) {
				$result_data['errors'] = $validation_result->get_error_data();
			}

			// Additional schema validation
			$schema_result = $schema_validator->validate_mcp_tool( $tool_data );
			if ( is_wp_error( $schema_result ) ) {
				$result_data['status'] = 'failed';
				$result_data['errors'] = array_merge( 
					$result_data['errors'], 
					$schema_validator->get_validation_errors() 
				);
			}

			$validation_results[] = $result_data;
		}

		$progress->finish();

		$this->output_results( $validation_results, $format );
		$this->display_summary( $validation_results );
	}

	/**
	 * Get all registered MCP tools
	 *
	 * @return array Registered tools data.
	 */
	private function get_registered_tools(): array {
		global $wp_mcp_tools;

		if ( empty( $wp_mcp_tools ) ) {
			// Try to get tools from RegisterMcpTool if available
			if ( class_exists( 'McpForWoo\Core\RegisterMcpTool' ) ) {
				$register_tool = new RegisterMcpTool();
				// This would need a method to retrieve registered tools
				// For now, return empty array
				return array();
			}
			return array();
		}

		return $wp_mcp_tools;
	}

	/**
	 * Output validation results
	 *
	 * @param array  $results Validation results.
	 * @param string $format Output format.
	 */
	private function output_results( array $results, string $format ): void {
		switch ( $format ) {
			case 'json':
				WP_CLI::print_value( $results, array( 'format' => 'json' ) );
				break;

			case 'yaml':
				WP_CLI::print_value( $results, array( 'format' => 'yaml' ) );
				break;

			case 'table':
			default:
				$table_data = array();
				foreach ( $results as $result ) {
					$table_data[] = array(
						'Tool'   => $result['tool'],
						'Status' => $result['status'],
						'Errors' => empty( $result['errors'] ) ? '-' : implode( '; ', array_slice( $result['errors'], 0, 2 ) ),
					);
				}

				if ( ! empty( $table_data ) ) {
					WP_CLI\Utils\format_items( 'table', $table_data, array( 'Tool', 'Status', 'Errors' ) );
				}
				break;
		}
	}

	/**
	 * Display validation summary
	 *
	 * @param array $results Validation results.
	 */
	private function display_summary( array $results ): void {
		$total_tools = count( $results );
		$passed_tools = count( array_filter( $results, function( $result ) {
			return 'passed' === $result['status'];
		} ) );
		$failed_tools = $total_tools - $passed_tools;

		WP_CLI::log( '' );
		WP_CLI::log( 'Validation Summary:' );
		WP_CLI::log( "- Total tools: {$total_tools}" );
		
		if ( $passed_tools > 0 ) {
			WP_CLI::success( "Passed: {$passed_tools}" );
		}
		
		if ( $failed_tools > 0 ) {
			WP_CLI::error( "Failed: {$failed_tools}", false );
			
			// Show detailed errors for failed tools
			WP_CLI::log( '' );
			WP_CLI::log( 'Failed Tools Details:' );
			foreach ( $results as $result ) {
				if ( 'failed' === $result['status'] ) {
					WP_CLI::log( "- {$result['tool']}:" );
					foreach ( $result['errors'] as $error ) {
						WP_CLI::log( "  • {$error}" );
					}
				}
			}
		}

		if ( 0 === $failed_tools ) {
			WP_CLI::success( 'All tools passed validation!' );
		}
	}
}

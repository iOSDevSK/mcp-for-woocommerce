<?php
/**
 * Enhanced Schema Validator
 *
 * @package WordPressMCP
 * @subpackage Utils
 */

namespace WordPressMCP\Utils;

use InvalidArgumentException;
use WP_Error;

/**
 * Class SchemaValidator
 *
 * Comprehensive JSON schema validation with caching and advanced constraint validation.
 */
class SchemaValidator {
	/**
	 * Schema cache for performance optimization
	 *
	 * @var array
	 */
	private static array $schema_cache = array();

	/**
	 * Cache TTL in seconds (1 hour default)
	 *
	 * @var int
	 */
	private int $cache_ttl = 3600;

	/**
	 * Validation errors collected during validation
	 *
	 * @var array
	 */
	private array $validation_errors = array();

	/**
	 * Constructor
	 *
	 * @param int $cache_ttl Cache TTL in seconds.
	 */
	public function __construct( int $cache_ttl = 3600 ) {
		$this->cache_ttl = $cache_ttl;
	}

	/**
	 * Primary validation method
	 *
	 * @param mixed $data The data to validate.
	 * @param array $schema The schema to validate against.
	 * @return bool|WP_Error True if valid, WP_Error object if validation fails.
	 */
	public function validate_against_schema( $data, array $schema ) {
		$this->validation_errors = array();

		try {
			$this->validate_schema_recursive( $data, $schema );
			return empty( $this->validation_errors ) ? true : new WP_Error( 'schema_validation_failed', 'Schema validation failed', $this->validation_errors );
		} catch ( InvalidArgumentException $e ) {
			$this->validation_errors[] = $e->getMessage();
			return new WP_Error( 'schema_validation_exception', $e->getMessage(), $this->validation_errors );
		}
	}

	/**
	 * Core MCP schema validation
	 *
	 * @param array $tool The MCP tool to validate.
	 * @return bool|WP_Error True if valid, WP_Error object if validation fails.
	 */
	public function validate_mcp_tool( array $tool ) {
		$mcp_schema = $this->get_mcp_schema();
		return $this->validate_against_schema( $tool, $mcp_schema );
	}

	/**
	 * WordPress extension schema validation
	 *
	 * @param array $tool The WordPress tool to validate.
	 * @return bool|WP_Error True if valid, WP_Error object if validation fails.
	 */
	public function validate_wordpress_tool( array $tool ) {
		$wordpress_schema = $this->get_wordpress_extensions_schema();
		return $this->validate_against_schema( $tool, $wordpress_schema );
	}

	/**
	 * Deep recursive validation
	 *
	 * @param mixed $data The data to validate.
	 * @param array $schema The schema to validate against.
	 * @param string $path Current validation path for error reporting.
	 * @throws InvalidArgumentException If validation fails.
	 */
	private function validate_schema_recursive( $data, array $schema, string $path = '' ): void {
		// Type validation
		if ( isset( $schema['type'] ) ) {
			$this->validate_type( $data, $schema['type'], $path );
		}

		// Object properties validation
		if ( 'object' === $schema['type'] && is_array( $data ) ) {
			$this->validate_object_properties( $data, $schema, $path );
		}

		// Array items validation
		if ( 'array' === $schema['type'] && is_array( $data ) ) {
			$this->validate_array_items( $data, $schema, $path );
		}

		// String constraints
		if ( 'string' === $schema['type'] && is_string( $data ) ) {
			$this->validate_string_constraints( $data, $schema, $path );
		}

		// Number constraints
		if ( in_array( $schema['type'], array( 'number', 'integer' ), true ) && is_numeric( $data ) ) {
			$this->validate_number_constraints( $data, $schema, $path );
		}

		// Enum validation
		if ( isset( $schema['enum'] ) ) {
			$this->validate_enum( $data, $schema['enum'], $path );
		}

		// One-of validation
		if ( isset( $schema['oneOf'] ) ) {
			$this->validate_one_of( $data, $schema['oneOf'], $path );
		}

		// Any-of validation
		if ( isset( $schema['anyOf'] ) ) {
			$this->validate_any_of( $data, $schema['anyOf'], $path );
		}
	}

	/**
	 * Validate data type
	 *
	 * @param mixed  $data The data to validate.
	 * @param string $expected_type Expected type.
	 * @param string $path Current path.
	 */
	private function validate_type( $data, string $expected_type, string $path ): void {
		$actual_type = $this->get_json_type( $data );

		if ( $actual_type !== $expected_type ) {
			$this->validation_errors[] = "Type mismatch at '{$path}': expected {$expected_type}, got {$actual_type}";
		}
	}

	/**
	 * Validate object properties
	 *
	 * @param array  $data The data to validate.
	 * @param array  $schema The schema.
	 * @param string $path Current path.
	 */
	private function validate_object_properties( array $data, array $schema, string $path ): void {
		// Required properties
		if ( isset( $schema['required'] ) && is_array( $schema['required'] ) ) {
			foreach ( $schema['required'] as $required_prop ) {
				if ( ! array_key_exists( $required_prop, $data ) ) {
					$this->validation_errors[] = "Required property '{$required_prop}' missing at '{$path}'";
				}
			}
		}

		// Property validation
		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( $data as $property => $value ) {
				if ( isset( $schema['properties'][ $property ] ) ) {
					$property_path = $path ? "{$path}.{$property}" : $property;
					$this->validate_schema_recursive( $value, $schema['properties'][ $property ], $property_path );
				} elseif ( isset( $schema['additionalProperties'] ) && false === $schema['additionalProperties'] ) {
					$this->validation_errors[] = "Additional property '{$property}' not allowed at '{$path}'";
				}
			}
		}
	}

	/**
	 * Validate array items
	 *
	 * @param array  $data The data to validate.
	 * @param array  $schema The schema.
	 * @param string $path Current path.
	 */
	private function validate_array_items( array $data, array $schema, string $path ): void {
		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			foreach ( $data as $index => $item ) {
				$item_path = "{$path}[{$index}]";
				$this->validate_schema_recursive( $item, $schema['items'], $item_path );
			}
		}

		// Min/max items
		if ( isset( $schema['minItems'] ) && count( $data ) < $schema['minItems'] ) {
			$this->validation_errors[] = "Array at '{$path}' has fewer items than minimum ({$schema['minItems']})";
		}

		if ( isset( $schema['maxItems'] ) && count( $data ) > $schema['maxItems'] ) {
			$this->validation_errors[] = "Array at '{$path}' has more items than maximum ({$schema['maxItems']})";
		}
	}

	/**
	 * Validate string constraints
	 *
	 * @param string $data The data to validate.
	 * @param array  $schema The schema.
	 * @param string $path Current path.
	 */
	private function validate_string_constraints( string $data, array $schema, string $path ): void {
		// Min/max length
		if ( isset( $schema['minLength'] ) && strlen( $data ) < $schema['minLength'] ) {
			$this->validation_errors[] = "String at '{$path}' is shorter than minimum length ({$schema['minLength']})";
		}

		if ( isset( $schema['maxLength'] ) && strlen( $data ) > $schema['maxLength'] ) {
			$this->validation_errors[] = "String at '{$path}' is longer than maximum length ({$schema['maxLength']})";
		}

		// Pattern validation
		if ( isset( $schema['pattern'] ) && ! preg_match( "/{$schema['pattern']}/", $data ) ) {
			$this->validation_errors[] = "String at '{$path}' does not match pattern '{$schema['pattern']}'";
		}
	}

	/**
	 * Validate number constraints
	 *
	 * @param mixed  $data The data to validate.
	 * @param array  $schema The schema.
	 * @param string $path Current path.
	 */
	private function validate_number_constraints( $data, array $schema, string $path ): void {
		$number = is_string( $data ) ? floatval( $data ) : $data;

		// Min/max value
		if ( isset( $schema['minimum'] ) && $number < $schema['minimum'] ) {
			$this->validation_errors[] = "Number at '{$path}' is less than minimum ({$schema['minimum']})";
		}

		if ( isset( $schema['maximum'] ) && $number > $schema['maximum'] ) {
			$this->validation_errors[] = "Number at '{$path}' is greater than maximum ({$schema['maximum']})";
		}

		// Multiple of
		if ( isset( $schema['multipleOf'] ) && 0 !== fmod( $number, $schema['multipleOf'] ) ) {
			$this->validation_errors[] = "Number at '{$path}' is not a multiple of {$schema['multipleOf']}";
		}
	}

	/**
	 * Validate enum constraint
	 *
	 * @param mixed  $data The data to validate.
	 * @param array  $enum_values Allowed enum values.
	 * @param string $path Current path.
	 */
	private function validate_enum( $data, array $enum_values, string $path ): void {
		if ( ! in_array( $data, $enum_values, true ) ) {
			$allowed = implode( ', ', $enum_values );
			$this->validation_errors[] = "Value at '{$path}' is not one of allowed values: {$allowed}";
		}
	}

	/**
	 * Validate one-of constraint
	 *
	 * @param mixed  $data The data to validate.
	 * @param array  $schemas Array of schemas.
	 * @param string $path Current path.
	 */
	private function validate_one_of( $data, array $schemas, string $path ): void {
		$valid_count = 0;

		foreach ( $schemas as $schema ) {
			$temp_validator = new self( $this->cache_ttl );
			$result         = $temp_validator->validate_against_schema( $data, $schema );
			if ( true === $result ) {
				$valid_count++;
			}
		}

		if ( 1 !== $valid_count ) {
			$this->validation_errors[] = "Value at '{$path}' must match exactly one of the provided schemas, but matches {$valid_count}";
		}
	}

	/**
	 * Validate any-of constraint
	 *
	 * @param mixed  $data The data to validate.
	 * @param array  $schemas Array of schemas.
	 * @param string $path Current path.
	 */
	private function validate_any_of( $data, array $schemas, string $path ): void {
		$valid = false;

		foreach ( $schemas as $schema ) {
			$temp_validator = new self( $this->cache_ttl );
			$result         = $temp_validator->validate_against_schema( $data, $schema );
			if ( true === $result ) {
				$valid = true;
				break;
			}
		}

		if ( ! $valid ) {
			$this->validation_errors[] = "Value at '{$path}' must match at least one of the provided schemas";
		}
	}

	/**
	 * Get JSON Schema type for PHP value
	 *
	 * @param mixed $value The value to get type for.
	 * @return string JSON Schema type.
	 */
	private function get_json_type( $value ): string {
		if ( is_null( $value ) ) {
			return 'null';
		}
		if ( is_bool( $value ) ) {
			return 'boolean';
		}
		if ( is_int( $value ) ) {
			return 'integer';
		}
		if ( is_float( $value ) ) {
			return 'number';
		}
		if ( is_string( $value ) ) {
			return 'string';
		}
		if ( is_array( $value ) ) {
			return array_values( $value ) === $value ? 'array' : 'object';
		}
		return 'unknown';
	}

	/**
	 * Get MCP schema definition
	 *
	 * @return array MCP schema.
	 */
	private function get_mcp_schema(): array {
		$cache_key = 'mcp_schema_' . md5( 'mcp-2025-06-18' );

		if ( isset( self::$schema_cache[ $cache_key ] ) ) {
			$cached = self::$schema_cache[ $cache_key ];
			if ( $cached['timestamp'] + $this->cache_ttl > time() ) {
				return $cached['schema'];
			}
		}

		// Basic MCP tool schema definition
		$schema = array(
			'$schema'    => 'https://json-schema.org/draft/2020-12/schema',
			'type'       => 'object',
			'required'   => array( 'name', 'inputSchema' ),
			'properties' => array(
				'name'        => array(
					'type'      => 'string',
					'pattern'   => '^[a-zA-Z0-9_-]{1,64}$',
					'maxLength' => 64,
				),
				'description' => array(
					'type' => 'string',
				),
				'inputSchema' => array(
					'type'       => 'object',
					'required'   => array( 'type' ),
					'properties' => array(
						'type'       => array(
							'type' => 'string',
							'enum' => array( 'object' ),
						),
						'properties' => array(
							'type' => 'object',
						),
						'required'   => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
					),
				),
				'annotations' => array(
					'type'       => 'object',
					'properties' => array(
						'title'           => array( 'type' => 'string' ),
						'readOnlyHint'    => array( 'type' => 'boolean' ),
						'destructiveHint' => array( 'type' => 'boolean' ),
						'idempotentHint'  => array( 'type' => 'boolean' ),
						'openWorldHint'   => array( 'type' => 'boolean' ),
					),
				),
			),
		);

		self::$schema_cache[ $cache_key ] = array(
			'schema'    => $schema,
			'timestamp' => time(),
		);

		return $schema;
	}

	/**
	 * Get WordPress extensions schema definition
	 *
	 * @return array WordPress extensions schema.
	 */
	private function get_wordpress_extensions_schema(): array {
		$cache_key = 'wordpress_extensions_schema';

		if ( isset( self::$schema_cache[ $cache_key ] ) ) {
			$cached = self::$schema_cache[ $cache_key ];
			if ( $cached['timestamp'] + $this->cache_ttl > time() ) {
				return $cached['schema'];
			}
		}

		// WordPress-specific extensions
		$schema = array(
			'$schema'    => 'https://json-schema.org/draft/2020-12/schema',
			'type'       => 'object',
			'properties' => array(
				'callback'   => array(
					'type' => 'string',
				),
				'enabled'    => array(
					'type' => 'boolean',
				),
				'rest_alias' => array(
					'type' => 'string',
				),
				'capability' => array(
					'type' => 'string',
				),
			),
		);

		self::$schema_cache[ $cache_key ] = array(
			'schema'    => $schema,
			'timestamp' => time(),
		);

		return $schema;
	}

	/**
	 * Get validation errors
	 *
	 * @return array Array of validation error messages.
	 */
	public function get_validation_errors(): array {
		return $this->validation_errors;
	}

	/**
	 * Clear schema cache
	 */
	public static function clear_cache(): void {
		self::$schema_cache = array();
	}
}
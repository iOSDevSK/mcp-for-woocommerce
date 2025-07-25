<?php
/**
 * Enhanced Tool Validator with multiple validation levels
 *
 * @package WordPressMCP
 * @subpackage Utils
 */

namespace WordPressMCP\Utils;

use InvalidArgumentException;
use WP_Error;

/**
 * Class ToolValidator
 *
 * Validates tools against a provided schema with multiple validation levels.
 */
class ToolValidator {
	/**
	 * The schema to validate against.
	 *
	 * @var array
	 */
	private array $schema;

	/**
	 * Validation level: 'strict', 'extended', or 'permissive'
	 *
	 * @var string
	 */
	private string $validation_level;

	/**
	 * Validation errors collected during validation
	 *
	 * @var array
	 */
	private array $validation_errors = array();

	/**
	 * Constructor.
	 *
	 * @param array  $schema The schema to validate against.
	 * @param string $validation_level Validation level: 'strict', 'extended', 'permissive'.
	 */
	public function __construct( array $schema, string $validation_level = 'extended' ) {
		$this->schema           = $schema;
		$this->validation_level = $validation_level;
	}

	/**
	 * Validates a tool against the schema.
	 *
	 * @param array $tool The tool to validate.
	 * @return bool|WP_Error True if valid, WP_Error object if validation fails.
	 */
	public function validate( array $tool ) {
		$this->validation_errors = array();

		try {
			// Validate required fields.
			$this->validateRequiredFields( $tool );

			// Validate name format.
			$this->validateName( $tool['name'] );

			// Validate input schema.
			$this->validateInputSchema( $tool['inputSchema'] );

			// Validate annotations if present.
			if ( isset( $tool['annotations'] ) ) {
				$this->validateAnnotations( $tool['annotations'] );
			}

			// Validate callback if present (extended/strict modes)
			if ( in_array( $this->validation_level, array( 'extended', 'strict' ), true ) ) {
				$this->validateCallback( $tool );
			}

			// Validate tool enablement (strict mode only)
			if ( 'strict' === $this->validation_level ) {
				$this->validateToolEnabledStatus( $tool );
			}

			return empty( $this->validation_errors ) ? true : new WP_Error( 'validation_failed', 'Tool validation failed', $this->validation_errors );

		} catch ( InvalidArgumentException $e ) {
			$this->validation_errors[] = $e->getMessage();
			return new WP_Error( 'validation_exception', $e->getMessage(), $this->validation_errors );
		}
	}

	/**
	 * Strict MCP compliance validation
	 *
	 * @param array $tool The tool to validate.
	 * @return bool|WP_Error True if valid, WP_Error object if validation fails.
	 */
	public function validate_mcp_strict( array $tool ) {
		$old_level              = $this->validation_level;
		$this->validation_level = 'strict';
		$result                 = $this->validate( $tool );
		$this->validation_level = $old_level;
		return $result;
	}

	/**
	 * WordPress-specific extended validation
	 *
	 * @param array $tool The tool to validate.
	 * @return bool|WP_Error True if valid, WP_Error object if validation fails.
	 */
	public function validate_wordpress_extended( array $tool ) {
		$old_level              = $this->validation_level;
		$this->validation_level = 'extended';
		$result                 = $this->validate( $tool );
		$this->validation_level = $old_level;
		return $result;
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
	 * Validates required fields are present.
	 *
	 * @param array $tool The tool to validate.
	 * @throws InvalidArgumentException If required fields are missing.
	 */
	private function validateRequiredFields( array $tool ): void {
		$requiredFields = array( 'name', 'inputSchema' );

		foreach ( $requiredFields as $field ) {
			if ( ! isset( $tool[ $field ] ) ) {
				throw new InvalidArgumentException( "Missing required field: {$field}" );
			}
		}
	}

	/**
	 * Validates the tool name format.
	 *
	 * @param string $name The name to validate.
	 * @throws InvalidArgumentException If name format is invalid.
	 */
	private function validateName( string $name ): void {
		if ( empty( $name ) ) {
			throw new InvalidArgumentException( 'Tool name cannot be empty.' );
		}

		if ( strlen( $name ) > 64 ) {
			throw new InvalidArgumentException( 'Tool name must be 64 characters or less.' );
		}

		if ( ! preg_match( '/^[a-zA-Z0-9_-]{1,64}$/', $name ) ) {
			throw new InvalidArgumentException( "Tool name should match pattern '^[a-zA-Z0-9_-]{1,64}$'. Received: '{$name}'." );
		}
	}

	/**
	 * Validates the input schema.
	 *
	 * @param array $inputSchema The input schema to validate.
	 * @throws InvalidArgumentException If input schema is invalid.
	 */
	private function validateInputSchema( array $inputSchema ): void {
		// Validate schema type.
		if ( ! isset( $inputSchema['type'] ) || $inputSchema['type'] !== 'object' ) {
			throw new InvalidArgumentException( 'Input schema must have type: "object".' );
		}

		// Validate properties if present.
		if ( isset( $inputSchema['properties'] ) ) {
			if ( ! is_array( $inputSchema['properties'] ) ) {
				throw new InvalidArgumentException( 'Input schema properties must be an array.' );
			}

			foreach ( $inputSchema['properties'] as $property => $schema ) {
				// Validate property key format.
				if ( ! preg_match( '/^[a-zA-Z0-9_-]{1,64}$/', $property ) ) {
					throw new InvalidArgumentException( "Property keys should match pattern '^[a-zA-Z0-9_-]{1,64}$'. Received: '{$property}'." );
				}

				// Validate property schema.
				if ( ! is_array( $schema ) ) {
					throw new InvalidArgumentException( "Property schema for '{$property}' must be an array." );
				}
			}
		}

		// Validate required fields if present.
		if ( isset( $inputSchema['required'] ) ) {
			if ( ! is_array( $inputSchema['required'] ) ) {
				throw new InvalidArgumentException( 'Input schema required fields must be an array.' );
			}

			foreach ( $inputSchema['required'] as $required ) {
				if ( ! is_string( $required ) ) {
					throw new InvalidArgumentException( 'Required field names must be strings.' );
				}
			}
		}
	}

	/**
	 * Validates tool annotations.
	 *
	 * @param array $annotations The annotations to validate.
	 * @throws InvalidArgumentException If annotations are invalid.
	 */
	private function validateAnnotations( array $annotations ): void {
		$validAnnotations = array(
			'title'           => 'string',
			'readOnlyHint'    => 'boolean',
			'destructiveHint' => 'boolean',
			'idempotentHint'  => 'boolean',
			'openWorldHint'   => 'boolean',
		);

		foreach ( $annotations as $key => $value ) {
			if ( ! isset( $validAnnotations[ $key ] ) ) {
				throw new InvalidArgumentException( "Invalid annotation key: {$key}." );
			}

			$expectedType = $validAnnotations[ $key ];
			$actualType   = gettype( $value );

			if ( $actualType !== $expectedType ) {
				throw new InvalidArgumentException( "Annotation '{$key}' must be of type {$expectedType}, got {$actualType}." );
			}
		}
	}

	/**
	 * Validates tool callback function exists and is callable
	 *
	 * @param array $tool The tool to validate.
	 * @throws InvalidArgumentException If callback is invalid.
	 */
	private function validateCallback( array $tool ): void {
		if ( ! isset( $tool['callback'] ) ) {
			$this->validation_errors[] = 'Tool callback is missing.';
			return;
		}

		if ( ! is_callable( $tool['callback'] ) ) {
			$this->validation_errors[] = 'Tool callback is not callable.';
		}
	}

	/**
	 * Validates tool enablement status
	 *
	 * @param array $tool The tool to validate.
	 * @throws InvalidArgumentException If enablement status is invalid.
	 */
	private function validateToolEnabledStatus( array $tool ): void {
		if ( isset( $tool['enabled'] ) && ! is_bool( $tool['enabled'] ) ) {
			$this->validation_errors[] = 'Tool enabled status must be boolean.';
		}
	}

	/**
	 * Enhanced input schema validation with deeper type checking
	 *
	 * @param array $inputSchema The input schema to validate.
	 * @throws InvalidArgumentException If input schema is invalid.
	 */
	private function validateInputSchemaEnhanced( array $inputSchema ): void {
		$this->validateInputSchema( $inputSchema );

		// Additional validations for strict mode
		if ( 'strict' === $this->validation_level ) {
			// Validate schema format compliance
			if ( isset( $inputSchema['$schema'] ) ) {
				$validSchemas = array(
					'https://json-schema.org/draft/2020-12/schema',
					'http://json-schema.org/draft-07/schema#',
				);

				if ( ! in_array( $inputSchema['$schema'], $validSchemas, true ) ) {
					$this->validation_errors[] = 'Unsupported JSON schema version.';
				}
			}

			// Validate additional constraints
			if ( isset( $inputSchema['additionalProperties'] ) && ! is_bool( $inputSchema['additionalProperties'] ) ) {
				$this->validation_errors[] = 'additionalProperties must be boolean.';
			}
		}
	}
}

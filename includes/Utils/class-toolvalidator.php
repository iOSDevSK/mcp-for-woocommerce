<?php
/**
 * Tool Validator
 *
 * @package WordPressMCP
 * @subpackage Utils
 */

namespace WordPressMCP\Utils;

use InvalidArgumentException;

/**
 * Class ToolValidator
 *
 * Validates tools against a provided schema.
 */
class ToolValidator {
	/**
	 * The schema to validate against.
	 *
	 * @var array
	 */
	private array $schema;

	/**
	 * Constructor.
	 *
	 * @param array $schema The schema to validate against.
	 */
	public function __construct( array $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Validates a tool against the schema.
	 *
	 * @param array $tool The tool to validate.
	 * @return bool True if valid, throws exception if invalid.
	 * @throws InvalidArgumentException If validation fails.
	 */
	public function validate( array $tool ): bool {
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

		return true;
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
}

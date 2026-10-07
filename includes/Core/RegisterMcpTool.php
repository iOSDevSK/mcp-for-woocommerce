<?php
declare(strict_types=1);


namespace McpForWoo\Core;

use InvalidArgumentException;
use McpForWoo\Utils\InputSchema;

/**
 * Register an MCP tool.
 */
class RegisterMcpTool {

	/**
	 * The arguments.
	 *
	 * @var array
	 */
	private array $args;

	/**
	 * Constructor.
	 *
	 * @param array $args The arguments to register the MCP tool.
	 * @throws InvalidArgumentException When the arguments are invalid.
	 * @throws \RuntimeException When the tool is registered outside of mcpfowo_init action.
	 */
	public function __construct( array $args ) {
		if ( ! doing_action( 'mcpfowo_init' ) ) {
			throw new \RuntimeException( 'RegisterMcpTool can only be used within the mcpfowo_init action.' );
		}

		$this->args = $args;

		// Backward compatibility for permissions_callback.
		if ( isset( $this->args['permissions_callback'] ) ) {
			$this->args['permission_callback'] = $this->args['permissions_callback'];
			unset( $this->args['permissions_callback'] );
		}
		$this->validate_arguments();
		$this->register_tool();
	}

	/**
	 * Register the tool.
	 *
	 * @return void
	 */
	private function register_tool(): void {
		$this->args['inputSchema'] = InputSchema::clean( $this->args['inputSchema'] );
		mcpfowo_instance()->register_tool( $this->args );
	}

	/**
	 * Validate the arguments.
	 *
	 * @return void
	 * @throws InvalidArgumentException When the arguments are invalid.
	 */
	private function validate_arguments(): void {
		// name is required.
		if ( ! isset( $this->args['name'] ) ) {
			throw new InvalidArgumentException( 'The name is required.' );
		}

		// validate the name: must be a string and between 1 and 64 characters.
		if ( ! preg_match( '/^[a-zA-Z0-9_-]{1,64}$/', $this->args['name'] ) ) {
			throw new InvalidArgumentException( 'The name must be a string between 1 and 64 characters.' );
		}

		// description is required.
		if ( ! isset( $this->args['description'] ) ) {
			throw new InvalidArgumentException( 'The description is required.' );
		}

		// functionality_type is required.
		if ( ! isset( $this->args['type'] ) ) {
			throw new InvalidArgumentException( 'The functionality type is required.' );
		}

		// Every tool in this plugin is read-only.
		if ( 'read' !== $this->args['type'] ) {
			throw new InvalidArgumentException( 'The functionality type must be read.' );
		}

		// callback is required.
		if ( ! isset( $this->args['callback'] ) ) {
			throw new InvalidArgumentException( 'The callback is required.' );
		}

		// callback must be callable.
		if ( ! is_callable( $this->args['callback'] ) ) {
			throw new InvalidArgumentException( 'The callback must be a callable.' );
		}

		// permission_callback must be callable.
		if ( empty( $this->args['permission_callback'] ) ) {
			throw new InvalidArgumentException( 'The permission callback is required.' );
		}

		// permission_callback must be callable.
		if ( ! is_callable( $this->args['permission_callback'] ) ) {
			throw new InvalidArgumentException( 'The permission callback must be a callable.' );
		}

		// validate the input schema.
		$this->validate_input_schema();
	}

	/**
	 * Validate the input schema.
	 *
	 * @return void
	 * @throws InvalidArgumentException When the input schema is invalid.
	 */
	private function validate_input_schema(): void {
		// Check if the input schema is provided.
		if ( empty( $this->args['inputSchema'] ) ) {
			throw new InvalidArgumentException( 'The input schema is required.' );
		}

		// Validate that the input schema is a valid JSON Schema object.
		if ( ! isset( $this->args['inputSchema']['type'] ) || 'object' !== $this->args['inputSchema']['type'] ) {
			throw new InvalidArgumentException( esc_html__( 'The input schema must be an object type.', 'mcp-for-woocommerce' ) );
		}

		// Validate properties field exists and is an object.
		// If ( ! isset( $this->args['inputSchema']['properties'] ) || ! is_array($this->args['inputSchema']['properties'] ) ) {
		// throw new \InvalidArgumentException( esc_html__( 'The input schema must have a properties field that is an object.', 'mcp-for-woocommerce' ) );
		// }.

		// Validate each property has a type.
		foreach ( $this->args['inputSchema']['properties'] as $property_name => $property ) {
			if ( ! isset( $property['type'] ) ) {
				// translators: %s: Property name.
				throw new InvalidArgumentException( sprintf( esc_html__( "Property '%s' must have a type field.", 'mcp-for-woocommerce' ), esc_html( $property_name ) ) );
			}

			// Validate property type is a valid JSON Schema type.
			$valid_types = array( 'string', 'number', 'integer', 'boolean', 'array', 'object', 'null' );
			if ( ! in_array( $property['type'], $valid_types, true ) ) {
				// translators: 1: Property name, 2: Property type.
				throw new InvalidArgumentException( sprintf( esc_html__( "Property '%1\$s' has invalid type '%2\$s'.", 'mcp-for-woocommerce' ), esc_html( $property_name ), esc_html( $property['type'] ) ) );
			}

			// If the type is array, the validate items field exists.
			if ( 'array' === $property['type'] && ! isset( $property['items'] ) ) {
				// translators: %s: Property name.
				throw new InvalidArgumentException( sprintf( esc_html__( "Array property '%s' must have an items field.", 'mcp-for-woocommerce' ), esc_html( $property_name ) ) );
			}
		}

		// Validate the required field if present.
		if ( isset( $this->args['inputSchema']['required'] ) ) {
			// Ensure required field is an array.
			if ( ! is_array( $this->args['inputSchema']['required'] ) ) {
				throw new InvalidArgumentException( esc_html__( 'The required field must be an array.', 'mcp-for-woocommerce' ) );
			}

			// Check all required properties exist in properties.
			foreach ( $this->args['inputSchema']['required'] as $required_property ) {
				if ( ! isset( $this->args['inputSchema']['properties'][ $required_property ] ) ) {
					// translators: %s: Required property.
					throw new InvalidArgumentException( sprintf( esc_html__( "Required property '%s' does not exist in properties.", 'mcp-for-woocommerce' ), esc_html( $required_property ) ) );
				}
			}
		}
	}
}

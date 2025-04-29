<?php //phpcs:ignore
declare(strict_types=1);

namespace Automattic\WordpressMcp\Core;

use InvalidArgumentException;

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
	 */
	public function __construct( array $args ) {
		$this->args = $args;
		$this->validate_arguments();
		$this->register_tool();
	}

	/**
	 * Register the tool.
	 *
	 * @return void
	 */
	private function register_tool(): void {
		if ( ! empty( $this->args['rest_alias'] ) ) {
			$this->get_args_from_rest_api();
		} else {
			WPMCP()->register_tool( $this->args );
		}
	}

	/**
	 * Get the arguments from the rest api.
	 *
	 * @return void
	 */
	private function get_args_from_rest_api(): void {
		$method = $this->args['rest_alias']['method'];
		$route  = $this->args['rest_alias']['route'];

		// get a list of all registered rest routes.
		$routes = rest_get_server()->get_routes();
		// maybe use:  rest_get_server()->get_route_options( $route ) );
		$rest_route = $routes[ $route ] ?? null;
		if ( ! $rest_route ) {
			// translators: %s: Route.
			throw new InvalidArgumentException( sprintf( esc_html__( 'The route %s does not exist.', 'wordpress-mcp' ), esc_html( $route ) ) );
		}

		$rest_api = null;

		// the subarray should contain the method.
		foreach ( $rest_route as $endpoint ) {
			if ( isset( $endpoint['methods'][ $method ] ) && true === $endpoint['methods'][ $method ] ) {
				$rest_api = $endpoint;
				break;
			}
		}
		if ( ! $rest_api ) {
			throw new InvalidArgumentException( esc_html__( 'The method does not exist.', 'wordpress-mcp' ) );
		}

		// Convert REST API args to MCP input schema.
		$input_schema = array(
			'type'       => 'object',
			'properties' => array(),
			'required'   => array(),
		);

		foreach ( $rest_api['args'] as $arg_name => $arg_schema ) {
			$input_schema['properties'][ $arg_name ] = array(
				'type'        => $arg_schema['type'],
				'description' => $arg_schema['description'],
			);

			// Handle array items if present.
			if ( isset( $arg_schema['items'] ) ) {
				$input_schema['properties'][ $arg_name ]['items'] = $arg_schema['items'];
			}

			// Handle enums if present.
			if ( isset( $arg_schema['enum'] ) ) {
				$input_schema['properties'][ $arg_name ]['enum'] = $arg_schema['enum'];
			}

			// Handle default values if present.
			if ( isset( $arg_schema['default'] ) ) {
				$input_schema['properties'][ $arg_name ]['default'] = $arg_schema['default'];
			}

			// Handle format if present.
			if ( isset( $arg_schema['format'] ) ) {
				$input_schema['properties'][ $arg_name ]['format'] = $arg_schema['format'];
			}

			// Handle minimum/maximum if present.
			if ( isset( $arg_schema['minimum'] ) ) {
				$input_schema['properties'][ $arg_name ]['minimum'] = $arg_schema['minimum'];
			}
			if ( isset( $arg_schema['maximum'] ) ) {
				$input_schema['properties'][ $arg_name ]['maximum'] = $arg_schema['maximum'];
			}

			// If the parameter has no default value and is not explicitly optional, mark it as required.
			if ( isset( $arg_schema['required'] ) && true === $arg_schema['required'] ) {
				$input_schema['required'][] = $arg_name;
			}
		}

		// Update the args with the converted schema.
		$this->args['inputSchema']          = $input_schema;
		$this->args['callback']             = $rest_api['callback'];
		$this->args['permissions_callback'] = $rest_api['permission_callback'];

		// Register the tool with the converted schema.
		WPMCP()->register_tool( $this->args );
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

		// if rest_alias is provided, the rest of the arguments are not required.
		if ( isset( $this->args['rest_alias'] ) ) {
			$this->validate_rest_alias();
			return;
		}

		// callback is required.
		if ( ! isset( $this->args['callback'] ) ) {
			throw new InvalidArgumentException( 'The callback is required.' );
		}

		// callback must be callable.
		if ( ! is_callable( $this->args['callback'] ) ) {
			throw new InvalidArgumentException( 'The callback must be a callable.' );
		}

		// permissions_callback must be callable.
		if ( isset( $this->args['permissions_callback'] ) && ! is_callable( $this->args['permissions_callback'] ) ) {
			throw new InvalidArgumentException( 'The permissions callback must be a callable.' );
		}

		// validate the input schema.
		$this->validate_input_schema();
	}

	/**
	 * Validate the rest api alias.
	 *
	 * @return void
	 * @throws InvalidArgumentException When the rest api alias is invalid.
	 */
	private function validate_rest_alias(): void {
		// route is required.
		if ( ! isset( $this->args['rest_alias']['route'] ) ) {
			throw new InvalidArgumentException( 'The route is required.' );
		}

		// method is required.
		if ( ! isset( $this->args['rest_alias']['method'] ) ) {
			throw new InvalidArgumentException( 'The method is required.' );
		}

		// validate the method: must be one of the following: GET, POST, PUT, PATCH, DELETE.
		if ( ! in_array( $this->args['rest_alias']['method'], array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			throw new InvalidArgumentException( 'The method must be one of the following: GET, POST, PUT, PATCH, DELETE.' );
		}
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
			throw new InvalidArgumentException( esc_html__( 'The input schema must be an object type.', 'wordpress-mcp' ) );
		}

		// Validate properties field exists and is an object.
		// If ( ! isset( $this->args['inputSchema']['properties'] ) || ! is_array($this->args['inputSchema']['properties'] ) ) {
		// throw new \InvalidArgumentException( esc_html__( 'The input schema must have a properties field that is an object.', 'wordpress-mcp' ) );
		// }

		// Validate each property has a type.
		foreach ( $this->args['inputSchema']['properties'] as $property_name => $property ) {
			if ( ! isset( $property['type'] ) ) {
				// translators: %s: Property name.
				throw new InvalidArgumentException( sprintf( esc_html__( "Property '%s' must have a type field.", 'wordpress-mcp' ), esc_html( $property_name ) ) );
			}

			// Validate property type is a valid JSON Schema type.
			$valid_types = array( 'string', 'number', 'integer', 'boolean', 'array', 'object', 'null' );
			if ( ! in_array( $property['type'], $valid_types, true ) ) {
				// translators: 1: Property name, 2: Property type.
				throw new InvalidArgumentException( sprintf( esc_html__( "Property '%1\$s' has invalid type '%2\$s'.", 'wordpress-mcp' ), esc_html( $property_name ), esc_html( $property['type'] ) ) );
			}

			// If the type is array, the validate items field exists.
			if ( 'array' === $property['type'] && ! isset( $property['items'] ) ) {
				// translators: %s: Property name.
				throw new InvalidArgumentException( sprintf( esc_html__( "Array property '%s' must have an items field.", 'wordpress-mcp' ), esc_html( $property_name ) ) );
			}
		}

		// Validate the required field if present.
		if ( isset( $this->args['inputSchema']['required'] ) ) {
			// if ( ! is_array( $this->args['inputSchema']['required'] ) ) {
			// throw new \InvalidArgumentException( esc_html__( 'The required field must be an array.', 'wordpress-mcp' ) );
			// }

			// Check all required properties exist in properties.
			foreach ( $this->args['inputSchema']['required'] as $required_property ) {
				if ( ! isset( $this->args['inputSchema']['properties'][ $required_property ] ) ) {
					// translators: %s: Required property.
					throw new InvalidArgumentException( sprintf( esc_html__( "Required property '%s' does not exist in properties.", 'wordpress-mcp' ), esc_html( $required_property ) ) );
				}
			}
		}
	}
}

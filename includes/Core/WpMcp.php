<?php
declare(strict_types=1);

namespace McpForWoo\Core;

use McpForWoo\Tools\McpWordPressPosts;
use McpForWoo\Tools\McpWordPressPages;

use McpForWoo\Tools\McpRestApiCrud;
use McpForWoo\Tools\McpWooProducts;
// use McpForWoo\Prompts\McpAnalyzeSales; // Disabled - not used in MCP for WooCommerce
use McpForWoo\Resources\McpWooSearchGuide;

use InvalidArgumentException;

use McpForWoo\Tools\McpWooCategories;
use McpForWoo\Tools\McpWooTags;
use McpForWoo\Tools\McpWooIntentAnalyzer;
use McpForWoo\Tools\McpWooReviews;
use McpForWoo\Tools\McpWooAttributes;
use McpForWoo\Tools\McpWooShipping;
use McpForWoo\Tools\McpWooTaxes;
use McpForWoo\Tools\McpWooPaymentGateways;
use McpForWoo\Tools\McpWooSystemStatus;
use McpForWoo\Tools\McpWooIntelligentSearch;

/**
 * WordPress MCP - WooCommerce Only
 *
 * @package WpMcp
 */
class WpMcp {

	/**
	 * The tools.
	 *
	 * @var array
	 */
	private array $tools = array();

	/**
	 * The tool callbacks.
	 *
	 * @var array
	 */
	private array $tools_callbacks = array();

	/**
	 * The resources.
	 *
	 * @var array
	 */
	private array $resources = array();

	/**
	 * The resource callbacks.
	 *
	 * @var array
	 */
	private array $resource_callbacks = array();

	/**
	 * The prompts.
	 *
	 * @var array
	 */
	private array $prompts = array();

	/**
	 * The prompt message.
	 *
	 * @var array
	 */
	private array $prompts_messages = array();

	/**
	 * The namespace.
	 *
	 * @var string
	 */
	private string $namespace = 'wpmcp/v1';

	/**
	 * The instance.
	 *
	 * @var ?WpMcp
	 */
	private static ?WpMcp $instance = null;

	/**
	 * The initialized flag.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * The MCP settings.
	 *
	 * @var array
	 */
	private array $mcp_settings = array();

	/**
	 * The has triggered init flag.
	 *
	 * @var bool
	 */
	private bool $has_triggered_init = false;

	/**
	 * The all tools.
	 *
	 * @var array
	 */
	private array $all_tools = array();

	/**
	 * The tool states option name.
	 *
	 * @var string
	 */
	private const TOOL_STATES_OPTION = 'mcpfowo_tool_states';

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Only initialize if not already initialized.
		if ( ! self::$initialized ) {
			$this->mcp_settings = get_option( 'mcpfowo_settings', array() );

			// Only initialize components if MCP is enabled.
			if ( $this->is_mcp_enabled() ) {
				$this->init_default_resources();
				$this->init_default_tools();
				$this->init_default_prompts();
				$this->init_features_as_tools();
				// Register the MCP assets earlier in the rest_api_init hook to prevent timeouts with Claude.ai web app.
				// Reduced priority from 20000 to 10 for faster initialization
				add_action( 'rest_api_init', array( $this, 'mcpfowo_init_action' ), 10 );

				self::$initialized = true;
			}
		}
	}

	/**
	 * Initialize the plugin.
	 */
	public function mcpfowo_init_action(): void {
		// Only trigger the mcpfowo_init action if MCP is enabled and hasn't been triggered before.
		if ( $this->is_mcp_enabled() && ! $this->has_triggered_init ) {
			// Log that the MCP init hook is firing to help diagnose registration timing
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			}
			do_action( 'mcpfowo_init', $this );
			$this->has_triggered_init = true;
		}
	}

	/**
	 * Check if MCP is enabled in settings.
	 *
	 * @return bool Whether MCP is enabled.
	 */
	private function is_mcp_enabled(): bool {
		return isset( $this->mcp_settings['enabled'] ) && $this->mcp_settings['enabled'];
	}

	/**
	 * Initialize the default resources (WooCommerce only).
	 */
	private function init_default_resources(): void {
		new McpWooSearchGuide();
	}

	/**
	 * Initialize the default tools (WooCommerce only).
	 */
	private function init_default_tools(): void {
		// Core WooCommerce tools
		new McpWooProducts();
		new McpWooCategories();
		new McpWooTags(); 
		new McpWooIntentAnalyzer();
		new McpWooIntelligentSearch();
		
		// Additional WooCommerce tools
		new McpWooReviews();
		new McpWooAttributes();
		new McpWooShipping();
		new McpWooTaxes();
		new McpWooPaymentGateways();
		new McpWooSystemStatus();

		// WordPress Core tools - READ ONLY
    	new McpWordPressPosts();
    	new McpWordPressPages();
		
		// Keep REST API CRUD for experimental access
		new McpRestApiCrud();
	}

	/**
	 * Initialize the default prompts (WooCommerce only).
	 */
	private function init_default_prompts(): void {
		// new McpAnalyzeSales(); // Disabled - sales analysis prompt not used in MCP for WooCommerce
		
		// Add future prompts here when needed
	}

	/**
	 * Initialize the features as tools.
	 */
	private function init_features_as_tools(): void {
		$features_enabled = isset( $this->mcp_settings['features_adapter_enabled'] ) && $this->mcp_settings['features_adapter_enabled'];

		if ( $features_enabled ) {
			new WpFeaturesAdapter();
		}
	}

	/**
	 * Get the instance.
	 *
	 * @return WpMcp
	 */
	public static function instance(): WpMcp {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Check if a tool type is enabled.
	 *
	 * @param string $type The tool type to check.
	 * @return bool Whether the tool type is enabled.
	 */
	private function is_tool_type_enabled( string $type ): bool {

		// Read operations and action operations are always allowed if MCP is enabled.
		if ( 'read' === $type || 'action' === $type ) {
			return true;
		}

		// Check specific tool type settings.
		$type_settings_map = array(
			'create' => 'enable_create_tools',
			'update' => 'enable_update_tools',
			'delete' => 'enable_delete_tools',
		);

		// Check if the type exists in our mapping and is enabled.
		if ( isset( $type_settings_map[ $type ] ) ) {
			return isset( $this->mcp_settings[ $type_settings_map[ $type ] ] ) && $this->mcp_settings[ $type_settings_map[ $type ] ];
		}

		return false;
	}

	/**
	 * Register a tool.
	 *
	 * @param array $args The arguments.
	 * @throws InvalidArgumentException If the tool name is not unique or if the tool type is disabled.
	 */
	public function register_tool( array $args ): void {
		$is_tool_type_enabled = $this->is_tool_type_enabled( $args['type'] );
		$is_tool_enabled      = $this->is_tool_enabled( $args['name'] );

		// Check if REST API CRUD tools are enabled and this tool should be disabled
		$is_rest_api_crud_enabled = ! empty( $this->mcp_settings['enable_rest_api_crud_tools'] );
		$has_rest_alias = ! empty( $args['rest_alias'] );
		$has_disabled_flag = ! empty( $args['disabled_by_rest_crud'] );
		$is_disabled_by_rest_crud = $is_rest_api_crud_enabled && ( $has_rest_alias || $has_disabled_flag );

		$args['tool_type_enabled'] = $is_tool_type_enabled;
		$args['tool_enabled']      = $is_tool_enabled;
		$args['disabled_by_rest_crud'] = $is_disabled_by_rest_crud;

		$this->all_tools[] = $args;

		// Skip actual registration if disabled by REST CRUD setting
		if ( $is_disabled_by_rest_crud ) {
			// Log reason for skip to aid debugging why tools are missing
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			}
			return;
		}
		// Check if the tool is enabled.
		if ( ! $is_tool_enabled || ! $is_tool_type_enabled ) {
			$error_bits = array();
			if ( ! $is_tool_enabled ) {
				$error_bits[] = 'user-disabled';
			}
			if ( ! $is_tool_type_enabled ) {
				$error_bits[] = 'type-disabled';
			}
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			}
			return; // Skip registration if tool is disabled.
		}

		// The name should be unique.
		if ( in_array( $args['name'], array_column( $this->tools, 'name' ), true ) ) {
			$this->tools_callbacks[ $args['name'] ] = array();

			// Search the tools array for the tool with the same name.
			foreach ( $this->tools as $tool ) {
				if ( $tool['name'] === $args['name'] ) {
					unset( $this->tools[ $tool['name'] ] );
					break;
				}
			}
		}

		$this->tools_callbacks[ $args['name'] ] = array(
			'callback'            => $args['callback'],
			'permission_callback' => $args['permission_callback'],
			'rest_alias'          => $args['rest_alias'] ?? null,
		);

		unset( $args['callback'] );
		unset( $args['permission_callback'] );
		unset( $args['rest_alias'] );
		unset( $args['disabled_by_rest_crud'] );
		$this->tools[] = $args;

		// Confirm registration with flags for easier remote debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		}
	}

	/**
	 * Register a resource.
	 *
	 * @param array $args The arguments.
	 * @throws InvalidArgumentException If the resource name or URI is not unique.
	 */
	public function register_resource( array $args ): void {
		// the name and uri should be unique.
		if ( in_array( $args['name'], array_column( $this->resources, 'name' ), true ) || in_array( $args['uri'], array_column( $this->resources, 'uri' ), true ) ) {
			$this->resources[ $args['uri'] ] = array();
		}
		$this->resources[ $args['uri'] ] = $args;
	}

	/**
	 * Register a resource callback.
	 *
	 * @param string   $uri The uri.
	 * @param callable $callback The callback.
	 */
	public function register_resource_callback( string $uri, callable $callback ): void {
		$this->resource_callbacks[ $uri ] = $callback;
	}

	/**
	 * Register a prompt.
	 *
	 * @param array $prompt    The prompt instance.
	 * @param array $messages  The messages for the prompt.
	 * @throws InvalidArgumentException If the prompt name is not unique.
	 */
	public function register_prompt( array $prompt, array $messages ): void {
		$name = $prompt['name'];

		// Check if the prompt name is unique.
		if ( isset( $this->prompts[ $name ] ) ) {
			$this->prompts[ $name ]          = array();
			$this->prompts_messages[ $name ] = array();
		}

		$this->prompts[ $name ]          = $prompt;
		$this->prompts_messages[ $name ] = $messages;
	}

	/**
	 * Get the tools.
	 *
	 * @return array
	 */
	public function get_tools(): array {
		return $this->tools;
	}

	/**
	 * Get all tools with enabled state.
	 *
	 * @return array
	 */
	public function get_all_tools(): array {
		$tool_states = get_option( self::TOOL_STATES_OPTION, array() );
		$tools       = $this->all_tools;

		// Add enabled state to each tool.
		foreach ( $tools as &$tool ) {
			$tool['enabled'] = ! isset( $tool_states[ $tool['name'] ] ) || $tool_states[ $tool['name'] ];
		}

		return $tools;
	}

	/**
	 * Get the tool callbacks.
	 *
	 * @return array
	 */
	public function get_tools_callbacks(): array {
		return $this->tools_callbacks;
	}

	/**
	 * Get the resources.
	 *
	 * @return array
	 */
	public function get_resources(): array {
		return $this->resources;
	}

	/**
	 * Get the resource callbacks.
	 *
	 * @return array
	 */
	public function get_resource_callbacks(): array {
		return $this->resource_callbacks;
	}

	/**
	 * Get the prompts.
	 *
	 * @return array
	 */
	public function get_prompts(): array {
		return $this->prompts;
	}

	/**
	 * Get a prompt by name.
	 *
	 * @param string $name The prompt name.
	 * @return array|null
	 */
	public function get_prompt_by_name( string $name ): ?array {
		return $this->prompts[ $name ] ?? null;
	}

	/**
	 * Get the prompt messages.
	 *
	 * @param string $name The prompt name.
	 * @return array|null
	 */
	public function get_prompt_messages( string $name ): ?array {
		return $this->prompts_messages[ $name ] ?? null;
	}

	/**
	 * Get the namespace.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Get a tool by name.
	 *
	 * @param string $name The tool name.
	 * @return array|null
	 */
	public function get_tool_by_name( string $name ): ?array {
		foreach ( $this->tools as $tool ) {
			if ( $tool['name'] === $name ) {
				return $tool;
			}
		}
		return null;
	}

	/**
	 * Get the MCP settings.
	 *
	 * @return array
	 */
	public function get_mcp_settings(): array {
		return $this->mcp_settings;
	}

	/**
	 * Check if a tool is enabled.
	 *
	 * @param string $tool_name The name of the tool to check.
	 * @return bool Whether the tool is enabled.
	 */
	public function is_tool_enabled( string $tool_name ): bool {
		$tool_states = get_option( self::TOOL_STATES_OPTION, array() );
		return ! isset( $tool_states[ $tool_name ] ) || $tool_states[ $tool_name ];
	}
}

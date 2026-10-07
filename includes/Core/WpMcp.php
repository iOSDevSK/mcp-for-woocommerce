<?php
declare(strict_types=1);

namespace McpForWoo\Core;

use McpForWoo\Tools\McpWordPressPosts;
use McpForWoo\Tools\McpWordPressPages;

use McpForWoo\Tools\McpWooProducts;
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

		// Published WordPress posts and pages.
		new McpWordPressPosts();
		new McpWordPressPages();
	}

	/**
	 * Initialize the default prompts (WooCommerce only).
	 */
	private function init_default_prompts(): void {
		// No prompts are registered by default.
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
	 * Register a tool.
	 *
	 * @param array $args The arguments.
	 * Only read-only tools are accepted: this plugin never creates, changes or
	 * deletes site data, so a tool of any other type is rejected outright.
	 *
	 * @param array $args The arguments.
	 * @throws InvalidArgumentException If the tool is not a read-only tool.
	 */
	public function register_tool( array $args ): void {
		if ( 'read' !== ( $args['type'] ?? '' ) ) {
			throw new InvalidArgumentException( 'Only read-only tools can be registered.' );
		}

		$is_tool_enabled      = $this->is_tool_enabled( $args['name'] );
		$args['tool_enabled'] = $is_tool_enabled;

		$this->all_tools[] = $args;

		// Skip registration if the site owner switched the tool off.
		if ( ! $is_tool_enabled ) {
			return;
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
		);

		unset( $args['callback'] );
		unset( $args['permission_callback'] );
		$this->tools[] = $args;
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
			// Handle integer storage: if not set, default enabled (true)
			// If set: 0, '0', '' = disabled, 1, '1' = enabled
			if ( ! isset( $tool_states[ $tool['name'] ] ) ) {
				$tool['enabled'] = true;
			} else {
				$state = $tool_states[ $tool['name'] ];
				$tool['enabled'] = ! empty( $state ) && $state !== '0' && $state !== 0;
			}
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
		// Handle integer storage: if not set, default enabled (true)
		// If set: 0, '0', '' = disabled, 1, '1' = enabled
		if ( ! isset( $tool_states[ $tool_name ] ) ) {
			return true;
		}
		$state = $tool_states[ $tool_name ];
		return ! empty( $state ) && $state !== '0' && $state !== 0;
	}
}

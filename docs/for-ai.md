Copy-paste this file into any AI chat or share it with a human developer. It is a single, compressed reference that explains what the plugin does, how its pieces fit together, and how to extend it with your own Tools, Resources, and Prompts.

---

## Summary

The WordPress MCP plugin implements the [Model Context Protocol](https://modelcontextprotocol.io) to enable AI systems to interact with WordPress sites through a standardized interface. The plugin provides three main extension points:

-   **Tools:** Actions AI can request the site to perform (e.g., creating posts, getting user info)
-   **Resources:** Data AI can request from the site (e.g., site settings, plugin information)
-   **Prompts:** Pre-defined templates for common AI tasks related to WordPress

This plugin serves as a bridge between AI models and WordPress, allowing secure, structured interactions with your site's content and functionality.

## Architecture Overview

WordPress MCP follows a modular architecture with these key components:

-   **Core:** Central classes managing registration and initialization
-   **Tools:** Interfaces for performing actions in WordPress
-   **Resources:** Data providers for site information
-   **Prompts:** Structured interaction templates
-   **Admin Interface:** Settings page for enabling/disabling features
-   **REST API Endpoints:** Communication channels for MCP clients

The plugin exposes a REST API endpoint at `/wp/v2/wpmcp` that handles all MCP operations using JSON-RPC style requests.

## Core Components

### WpMcp Class

The central class that manages registration of tools, resources, and prompts. It handles initialization and maintains references to all registered components.

```php
// Access the main plugin instance
$mcp = WPMCP();

// Get registered tools, resources, or prompts
$tools = $mcp->get_tools();
$resources = $mcp->get_resources();
$prompts = $mcp->get_prompts();
```

### Registration Classes

Three main classes handle registration of components:

-   `RegisterMcpTool`: Registers tools that perform actions
-   `RegisterMcpResource`: Registers resources that provide data
-   `RegisterMcpPrompt`: Registers prompts for structured interactions

## Extending the Plugin

### Adding New Tools

Tools are actions AI can perform in WordPress. There are four types:

-   `read`: For retrieving data (always allowed)
-   `create`: For creating new content (requires enabling in settings)
-   `update`: For modifying existing content (requires enabling in settings)
-   `delete`: For removing content (requires enabling in settings)

**Example: Creating a Custom Tool**

```php
use Automattic\WordpressMcp\Core\RegisterMcpTool;

class MyCustomTool {
    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools(): void {
        new RegisterMcpTool([
            'name'        => 'my_custom_tool',
            'description' => 'A custom tool that performs a specific action',
            'type'        => 'read',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'param1' => [
                        'type'        => 'string',
                        'description' => 'First parameter',
                    ],
                ],
                'required'   => ['param1'],
            ],
            'callback'    => [$this, 'tool_callback'],
            'permission_callback' => [$this, 'permission_callback'],
        ]);
    }

    public function tool_callback($args) {
        // Tool implementation
        return [
            'success' => true,
            'data'    => 'Result of the tool operation',
        ];
    }

    public function permission_callback() {
        // Check if the user has permission to use this tool
        return current_user_can('manage_options');
    }
}

// Initialize your custom tool
new MyCustomTool();
```

**REST API Integration**

You can also create tools that map to existing WordPress REST API endpoints:

```php
new RegisterMcpTool([
    'name'        => 'wp_custom_post_tool',
    'description' => 'Interact with custom post types',
    'type'        => 'read',
    'rest_alias'  => [
        'route'  => '/wp/v2/my-custom-post-type',
        'method' => 'GET',
    ],
]);
```

### Adding New Resources

Resources provide data to AI systems. They are registered with a URI, name, description, and MIME type.

**Example: Creating a Custom Resource**

```php
use Automattic\WordpressMcp\Core\RegisterMcpResource;

class MyCustomResource {
    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_resource']);
    }

    public function register_resource(): void {
        new RegisterMcpResource(
            [
                'uri'         => 'WordPress://my-custom-resource',
                'name'        => 'my-custom-resource',
                'description' => 'Provides custom data about the site',
                'mimeType'    => 'application/json',
            ],
            [$this, 'get_resource_data']
        );
    }

    public function get_resource_data($params = []) {
        // Resource implementation
        return [
            'custom_field' => 'Custom value',
            'timestamp'    => current_time('timestamp'),
            // Add other data as needed
        ];
    }
}

// Initialize your custom resource
new MyCustomResource();
```

### Adding New Prompts

Prompts are templates for structured AI interactions. They define a set of messages and can include arguments that are replaced at runtime.

**Example: Creating a Custom Prompt**

```php
use Automattic\WordpressMcp\Core\RegisterMcpPrompt;

class MyCustomPrompt {
    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_prompt']);
    }

    public function register_prompt(): void {
        new RegisterMcpPrompt(
            [
                'name'        => 'my-custom-prompt',
                'description' => 'A custom prompt for a specific task',
                'arguments'   => [
                    [
                        'name'        => 'input_data',
                        'description' => 'The data to process',
                        'required'    => true,
                        'type'        => 'string',
                    ],
                    [
                        'name'        => 'format',
                        'description' => 'The desired output format',
                        'required'    => false,
                        'type'        => 'string',
                    ],
                ],
            ],
            $this->messages()
        );
    }

    private function messages(): array {
        return [
            [
                'role'    => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Process the following data: {{input_data}}. Format the output as {{format|default:"JSON"}}.',
                ],
            ],
        ];
    }
}

// Initialize your custom prompt
new MyCustomPrompt();
```

## Tools and Resources Examples

### WordPress Posts Tools

```php
use Automattic\WordpressMcp\Core\RegisterMcpTool;

// Register a tool to search posts
new RegisterMcpTool([
    'name'        => 'custom_posts_search',
    'description' => 'Search and filter WordPress posts',
    'type'        => 'read',
    'rest_alias'  => [
        'route'  => '/wp/v2/posts',
        'method' => 'GET',
    ],
]);

// Register a tool to create posts
new RegisterMcpTool([
    'name'        => 'custom_add_post',
    'description' => 'Add a new WordPress post',
    'type'        => 'create',
    'rest_alias'  => [
        'route'  => '/wp/v2/posts',
        'method' => 'POST',
    ],
]);
```

### Site Information Resource

```php
use Automattic\WordpressMcp\Core\RegisterMcpResource;

new RegisterMcpResource(
    [
        'uri'         => 'WordPress://site-custom-info',
        'name'        => 'site-custom-info',
        'description' => 'Provides custom information about the WordPress site',
        'mimeType'    => 'application/json',
    ],
    function($params) {
        return [
            'site_name'         => get_bloginfo('name'),
            'site_url'          => get_bloginfo('url'),
            'site_description'  => get_bloginfo('description'),
            'custom_meta'       => get_option('my_custom_site_meta'),
            'theme_info'        => [
                'name'    => wp_get_theme()->get('Name'),
                'version' => wp_get_theme()->get('Version'),
            ],
        ];
    }
);
```

### WooCommerce Integration

```php
use Automattic\WordpressMcp\Core\RegisterMcpTool;

// Check if WooCommerce is active before registering WooCommerce-specific tools
if (class_exists('WooCommerce')) {
    // Register a tool to get WooCommerce products
    new RegisterMcpTool([
        'name'        => 'custom_wc_products',
        'description' => 'Get WooCommerce products',
        'type'        => 'read',
        'rest_alias'  => [
            'route'  => '/wc/v3/products',
            'method' => 'GET',
        ],
    ]);

    // Register a tool to analyze sales data
    new RegisterMcpTool([
        'name'                 => 'analyze_wc_sales',
        'description'          => 'Analyze WooCommerce sales data',
        'type'                 => 'read',
        'callback'             => function($args) {
            // Get sales data for the specified period
            $period = $args['period'] ?? 'last_30_days';

            // Get sales data from WooCommerce
            $orders = wc_get_orders([
                'limit'   => -1,
                'date_created' => '>' . strtotime('-30 days'),
                'status'  => ['completed', 'processing'],
            ]);

            // Calculate sales metrics
            $total_sales = 0;
            $order_count = count($orders);

            foreach ($orders as $order) {
                $total_sales += $order->get_total();
            }

            $average_order_value = $order_count > 0 ? $total_sales / $order_count : 0;

            return [
                'period'               => $period,
                'total_sales'          => $total_sales,
                'order_count'          => $order_count,
                'average_order_value'  => $average_order_value,
            ];
        },
        'permission_callback' => function() {
            return current_user_can('manage_woocommerce');
        },
        'inputSchema'          => [
            'type'       => 'object',
            'properties' => [
                'period' => [
                    'type'        => 'string',
                    'description' => 'The period to analyze (e.g., last_7_days, last_30_days, last_quarter)',
                    'default'     => 'last_30_days',
                ],
            ],
        ],
    ]);
}
```

## API Reference

### Core Classes

#### WpMcp

Main plugin class that manages registration and initialization.

**Methods:**

-   `instance()`: Returns the singleton instance
-   `register_tool($args)`: Registers a tool
-   `register_resource($args)`: Registers a resource
-   `register_resource_callback($uri, $callback)`: Registers a resource callback
-   `register_prompt($prompt, $messages)`: Registers a prompt
-   `get_tools()`: Returns all registered tools
-   `get_resources()`: Returns all registered resources
-   `get_prompts()`: Returns all registered prompts

#### RegisterMcpTool

Registers an MCP tool with the system.

**Constructor Parameters:**

-   `$args`: Array of tool arguments including:
    -   `name`: Unique identifier for the tool
    -   `description`: Human-readable description
    -   `type`: Tool type ('read', 'create', 'update', 'delete')
    -   `callback`: Function that implements the tool's logic
    -   `permission_callback`: Function that checks user permissions
    -   `inputSchema`: JSON Schema describing the tool's input parameters
    -   `rest_alias`: Optional mapping to a WordPress REST API endpoint

#### RegisterMcpResource

Registers an MCP resource with the system.

**Constructor Parameters:**

-   `$args`: Array of resource arguments including:
    -   `uri`: Unique URI identifier for the resource
    -   `name`: Human-readable name
    -   `description`: Description of the resource's purpose
    -   `mimeType`: MIME type of the resource content
-   `$resource_content_callback`: Function that provides the resource data

#### RegisterMcpPrompt

Registers an MCP prompt with the system.

**Constructor Parameters:**

-   `$prompt`: Array of prompt arguments including:
    -   `name`: Unique identifier for the prompt
    -   `description`: Description of the prompt's purpose
    -   `arguments`: Array of argument definitions
-   `$messages`: Array of message templates

## Settings & Configuration

The plugin's settings page is accessible at `Settings > MCP` in the WordPress admin dashboard. It provides options to enable/disable:

-   MCP functionality
-   WordPress Features Adapter
-   Create tools
-   Update tools
-   Delete tools

You can programmatically access these settings:

```php
$options = get_option('wordpress_mcp_settings', []);
$mcp_enabled = isset($options['enabled']) && $options['enabled'];
$create_tools_enabled = isset($options['enable_create_tools']) && $options['enable_create_tools'];
```

## WordPress Features API Integration

The plugin can optionally integrate with the [WordPress Feature API](https://github.com/Automattic/wp-feature-api) to expose registered features as MCP tools. This is handled by the `WpFeaturesAdapter` class.

To use this feature:

1. Install and activate the WordPress Feature API plugin
2. Enable "WordPress Features Adapter" in the MCP settings

## Notes for Developers

1. **Permissions**: All MCP operations require administrator privileges by default. Use the `permission_callback` when registering tools to set custom permission requirements.

2. **Security**: Be careful when enabling create/update/delete tools as they can modify your WordPress site content.

3. **REST API Integration**: The plugin exposes a single endpoint at `/wp/v2/wpmcp` that handles all MCP operations.

4. **Action Hooks**: The main action hook to use when extending the plugin is `wordpress_mcp_init`.

5. **Filter Hooks**: You can filter the registered tools, resources, and prompts using standard WordPress filters.

6. **Error Handling**: Always handle errors appropriately in your tool and resource callbacks.

7. **Tool Types**: Remember that 'read' tools are always available when MCP is enabled, but other types require explicit enabling in settings.

## Using the Plugin API in Your Theme or Plugin

To create extensions to the MCP plugin from your own theme or plugin:

```php
/**
 * Add custom MCP extensions
 */
function my_theme_register_mcp_extensions() {
    // Check if MCP plugin is active
    if (!function_exists('WPMCP')) {
        return;
    }

    // Register your custom tools, resources, and prompts
    add_action('wordpress_mcp_init', 'my_theme_register_mcp_tools');
    add_action('wordpress_mcp_init', 'my_theme_register_mcp_resources');
    add_action('wordpress_mcp_init', 'my_theme_register_mcp_prompts');
}
add_action('init', 'my_theme_register_mcp_extensions');

/**
 * Register custom MCP tools
 */
function my_theme_register_mcp_tools() {
    // Register your custom tools here
    new \Automattic\WordpressMcp\Core\RegisterMcpTool([
        'name'        => 'my_theme_custom_tool',
        'description' => 'A custom tool from my theme',
        'type'        => 'read',
        'callback'    => 'my_theme_custom_tool_callback',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'inputSchema' => [
            'type'       => 'object',
            'properties' => [],
        ],
    ]);
}

/**
 * Custom tool callback function
 */
function my_theme_custom_tool_callback($args) {
    // Implement your tool logic here
    return ['status' => 'success', 'data' => 'Custom tool executed successfully'];
}

// Similar functions for resources and prompts
```

## Troubleshooting

1. **Plugin Not Working**: Ensure MCP functionality is enabled in the settings.

2. **Tools Not Available**: Check that the appropriate tool types (create/update/delete) are enabled.

3. **WordPress Features Adapter Not Working**: Verify that the WordPress Feature API plugin is installed and activated.

4. **REST API Errors**: Check that the WordPress REST API is accessible and not blocked by security plugins.

5. **Permission Issues**: Ensure that the user has the appropriate permissions to use the tools/resources.

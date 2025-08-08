# RegisterMcpTools API Documentation

The RegisterMcpTools API allows you to register and manage tools that can be used by AI models to perform actions in WordPress.

## Overview

Tools are actions that can be performed by AI models. They are registered using the `RegisterMcpTool` class and can be of different types:

-   `read`: For retrieving data
-   `create`: For creating new content
-   `update`: For modifying existing content
-   `delete`: For removing content

## Basic Usage

```php
use Automattic\WordpressMcp\Core\RegisterMcpTool;

// Register a simple read tool
new RegisterMcpTool([
    'name'        => 'my_custom_tool',
    'description' => 'A custom tool for reading data',
    'type'        => 'read',
    'callback'    => function($params) {
        // Tool implementation
        return ['data' => 'example'];
    },
    'permission_callback' => function() {
        return current_user_can('manage_options');
    }
]);
```

## Tool Registration Parameters

| Parameter           | Type     | Required | Description                                               |
| ------------------- | -------- | -------- | --------------------------------------------------------- |
| name                | string   | Yes      | Unique identifier for the tool                            |
| description         | string   | Yes      | Human-readable description of the tool's purpose          |
| type                | string   | Yes      | Type of tool: 'read', 'create', 'update', or 'delete'     |
| callback            | callable | Yes      | Function that implements the tool's logic                 |
| permission_callback | callable | Yes      | Function that checks if the current user can use the tool |
| rest_alias          | array    | No       | Optional REST API endpoint configuration                  |

## REST API Integration

Tools can be integrated with WordPress REST API endpoints:

```php
new RegisterMcpTool([
    'name'        => 'wp_posts_search',
    'description' => 'Search and filter WordPress posts with pagination',
    'type'        => 'read',
    'rest_alias'  => [
        'route'  => '/wp/v2/posts',
        'method' => 'GET',
    ],
]);
```

## Tool Types and Permissions

Different tool types have different permission requirements:

1. **Read Tools**

    - Always allowed if MCP is enabled
    - Used for retrieving data
    - Example: `wp_posts_search`, `wp_get_post`

2. **Create Tools**

    - Requires `enable_create_tools` setting
    - Used for creating new content
    - Example: `wp_add_post`, `wp_add_user`

3. **Update Tools**

    - Requires `enable_update_tools` setting
    - Used for modifying existing content
    - Example: `wp_update_post`, `wp_update_user`

4. **Delete Tools**
    - Requires `enable_delete_tools` setting
    - Used for removing content
    - Example: `wp_delete_post`, `wp_delete_user`

## Error Handling

Tools should handle errors appropriately:

```php
new RegisterMcpTool([
    'name'        => 'my_tool',
    'description' => 'A tool with error handling',
    'type'        => 'read',
    'callback'    => function($params) {
        try {
            // Tool implementation
            return ['success' => true, 'data' => 'example'];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    },
    'permission_callback' => function() {
        return current_user_can('manage_options');
    }
]);
```

## Best Practices

1. **Unique Names**: Ensure tool names are unique across all registered tools
2. **Clear Descriptions**: Provide detailed descriptions of what the tool does
3. **Proper Permissions**: Always implement appropriate permission checks
4. **Error Handling**: Include proper error handling in callbacks
5. **Type Safety**: Use appropriate types for parameters and return values
6. **Documentation**: Document any required parameters or expected return values

## Example: Complete Tool Implementation

```php
use Automattic\WordpressMcp\Core\RegisterMcpTool;

class MyCustomTool {
    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_tools']);
    }

    public function register_tools() {
        new RegisterMcpTool([
            'name'        => 'my_custom_tool',
            'description' => 'A custom tool for managing custom data',
            'type'        => 'read',
            'callback'    => [$this, 'tool_callback'],
            'permission_callback' => [$this, 'check_permissions'],
            'rest_alias'  => [
                'route'  => '/wp/v2/my-custom-endpoint',
                'method' => 'GET',
            ],
        ]);
    }

    public function tool_callback($params) {
        try {
            // Tool implementation
            return [
                'success' => true,
                'data'    => $this->get_custom_data($params)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    public function check_permissions() {
        return current_user_can('manage_options');
    }

    private function get_custom_data($params) {
        // Implementation details
        return ['example' => 'data'];
    }
}
```

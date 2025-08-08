# RegisterMcpResources API Documentation

The RegisterMcpResources API allows you to register and manage resources that provide data to AI models in WordPress.

## Overview

Resources are data sources that can be accessed by AI models. They are registered using the `RegisterMcpResource` class and provide structured data in various formats.

## Basic Usage

```php
use Automattic\WordpressMcp\Core\RegisterMcpResource;

// Register a simple resource
new RegisterMcpResource(
    [
        'uri'         => 'WordPress://my-resource',
        'name'        => 'my-resource',
        'description' => 'A custom resource for providing data',
        'mimeType'    => 'application/json',
    ],
    function($params) {
        // Resource implementation
        return ['data' => 'example'];
    }
);
```

## Resource Registration Parameters

| Parameter   | Type     | Required | Description                                                  |
| ----------- | -------- | -------- | ------------------------------------------------------------ |
| uri         | string   | Yes      | Unique URI identifier for the resource                       |
| name        | string   | Yes      | Human-readable name of the resource                          |
| description | string   | Yes      | Description of the resource's purpose                        |
| mimeType    | string   | Yes      | MIME type of the resource content (e.g., 'application/json') |
| callback    | callable | Yes      | Function that provides the resource data                     |

## Resource Types

Resources can provide different types of data:

1. **JSON Resources**

   - MIME type: `application/json`
   - Used for structured data
   - Example: Plugin information, site settings

2. **Text Resources**
   - MIME type: `text/plain`
   - Used for unstructured text data
   - Example: Log files, configuration files

## Example: Plugin Information Resource

```php
use Automattic\WordpressMcp\Core\RegisterMcpResource;
use Automattic\WordpressMcp\Utils\PluginsInfo;

class McpPluginInfoResource {
    private $plugins_info;

    public function __construct() {
        $this->plugins_info = new PluginsInfo();
        add_action('wordpress_mcp_init', [$this, 'register_resource']);
    }

    public function register_resource() {
        new RegisterMcpResource(
            [
                'uri'         => 'WordPress://plugin-info',
                'name'        => 'plugin-info',
                'description' => 'Provides detailed information about active WordPress plugins',
                'mimeType'    => 'application/json',
            ],
            [$this, 'get_plugin_info']
        );
    }

    public function get_plugin_info($params = []) {
        return $this->plugins_info->get_plugins_info();
    }
}
```

## Resource Subscription

Resources can be subscribed to for real-time updates:

```php
// Subscribe to a resource
$subscription = wp_mcp_subscribe_resource('WordPress://plugin-info');

// Unsubscribe from a resource
wp_mcp_unsubscribe_resource($subscription['subscriptionId']);
```

## Error Handling

Resources should handle errors appropriately:

```php
new RegisterMcpResource(
    [
        'uri'         => 'WordPress://my-resource',
        'name'        => 'my-resource',
        'description' => 'A resource with error handling',
        'mimeType'    => 'application/json',
    ],
    function($params) {
        try {
            // Resource implementation
            return [
                'success' => true,
                'data'    => $this->get_resource_data($params)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
);
```

## Best Practices

1. **Unique URIs**: Ensure resource URIs are unique across all registered resources
2. **Clear Descriptions**: Provide detailed descriptions of the resource's data
3. **Proper MIME Types**: Use appropriate MIME types for the resource content
4. **Error Handling**: Include proper error handling in callbacks
5. **Data Validation**: Validate and sanitize resource data
6. **Caching**: Implement caching for frequently accessed resources
7. **Documentation**: Document the resource's data structure and available parameters

## Example: Complete Resource Implementation

```php
use Automattic\WordpressMcp\Core\RegisterMcpResource;

class MyCustomResource {
    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_resource']);
    }

    public function register_resource() {
        new RegisterMcpResource(
            [
                'uri'         => 'WordPress://my-custom-resource',
                'name'        => 'my-custom-resource',
                'description' => 'A custom resource for providing custom data',
                'mimeType'    => 'application/json',
            ],
            [$this, 'resource_callback']
        );
    }

    public function resource_callback($params) {
        try {
            return [
                'success' => true,
                'data'    => $this->get_resource_data($params)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    private function get_resource_data($params) {
        // Implementation details
        return [
            'example' => 'data',
            'timestamp' => time()
        ];
    }
}
```

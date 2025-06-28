# Registered Prompts Documentation

This document provides a comprehensive list of all prompts registered in the WordPress MCP plugin.

## Site Information Prompts

### get-site-info

- **Name**: get-site-info
- **Description**: Retrieves and analyzes general information about the WordPress site
- **Arguments**: None
- **Message Template**:
  ```
  Analyze the following WordPress site information and provide a summary:
  - Site name and description
  - WordPress version
  - Active theme
  - Active plugins
  - Site settings
  ```

## WooCommerce Prompts

### analyze-sales

- **Name**: analyze-sales
- **Description**: Analyzes WooCommerce sales data for a specified time period
- **Arguments**:
  - `time_span` (required): The time period to analyze (e.g., last_7_days, last_30_days, last_month, last_quarter, last_year)
- **Message Template**:
  ```
  Analyze the WooCommerce sales data for the time period: {{time_span}}. Include:
  - Total sales
  - Average order value
  - Top-selling products
  - Sales trends
  - Customer insights
  ```

## Usage Examples

### Getting Site Information

```php
$result = wp_mcp_execute_prompt('get-site-info');
```

### Analyzing Sales Data

```php
$result = wp_mcp_execute_prompt('analyze-sales', [
    'time_span' => 'last_30_days'
]);
```

## Prompt Response Format

All prompts return a standardized response format:

```php
[
    'success' => true|false,
    'data' => [
        'content' => [
            [
                'type' => 'text',
                'text' => 'string'  // The AI-generated response
            ]
        ]
    ],
    'error' => string|null  // Error message if success is false
]
```

## Creating Custom Prompts

You can create custom prompts using the `RegisterMcpPrompt` class:

```php
use Automattic\WordpressMcp\Core\RegisterMcpPrompt;

class MyCustomPrompt {
    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_prompt']);
    }

    public function register_prompt() {
        new RegisterMcpPrompt(
            [
                'name'        => 'my-custom-prompt',
                'description' => 'A custom prompt for specific analysis',
                'arguments'   => [
                    [
                        'name'        => 'input_data',
                        'description' => 'The data to analyze',
                        'required'    => true,
                        'type'        => 'string',
                    ],
                ],
            ],
            $this->messages()
        );
    }

    private function messages() {
        return [
            [
                'role'    => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Analyze the following data: {{input_data}}. Provide insights and recommendations.',
                ],
            ],
        ];
    }
}
```

## Best Practices for Creating Prompts

1. **Clear Purpose**: Define a clear purpose for each prompt
2. **Well-Defined Arguments**: Specify required and optional arguments
3. **Structured Messages**: Use consistent message structure
4. **Contextual Information**: Include relevant context in messages
5. **Error Handling**: Handle potential errors gracefully
6. **Testing**: Test prompts with various argument combinations

Prompts should handle errors appropriately:

```php
try {
    $result = wp_mcp_execute_prompt('analyze-sales', [
        'time_span' => 'last_30_days'
    ]);

    if (!$result['success']) {
        // Handle error
        error_log('Prompt execution failed: ' . $result['error']);
    }
} catch (Exception $e) {
    // Handle exception
    error_log('Exception in prompt execution: ' . $e->getMessage());
}
```

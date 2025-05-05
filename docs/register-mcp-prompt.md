# RegisterMcpPrompt API Documentation

The RegisterMcpPrompt API allows you to register and manage prompts that structure AI model interactions in WordPress.

## Overview

Prompts are predefined templates for AI model interactions. They are registered using the `RegisterMcpPrompt` class and provide structured ways to interact with AI models.

## Basic Usage

```php
use Automattic\WordpressMcp\Core\RegisterMcpPrompt;

// Register a simple prompt
new RegisterMcpPrompt(
    [
        'name'        => 'my-prompt',
        'description' => 'A custom prompt for AI interaction',
        'arguments'   => [
            [
                'name'        => 'param1',
                'description' => 'First parameter',
                'required'    => true,
            ],
        ],
    ],
    [
        [
            'role'    => 'user',
            'content' => [
                'type' => 'text',
                'text' => 'Process this data: {{param1}}',
            ],
        ],
    ]
);
```

## Prompt Registration Parameters

| Parameter   | Type   | Required | Description                         |
| ----------- | ------ | -------- | ----------------------------------- |
| name        | string | Yes      | Unique identifier for the prompt    |
| description | string | Yes      | Description of the prompt's purpose |
| arguments   | array  | No       | Array of argument definitions       |
| messages    | array  | Yes      | Array of message templates          |

## Argument Structure

Each argument in the `arguments` array should have:

| Parameter   | Type   | Required | Description                                       |
| ----------- | ------ | -------- | ------------------------------------------------- |
| name        | string | Yes      | Name of the argument                              |
| description | string | No       | Description of the argument                       |
| required    | bool   | No       | Whether the argument is required (default: false) |
| type        | string | No       | Type of the argument (default: string)            |

## Message Structure

Each message in the `messages` array should have:

| Parameter    | Type   | Required | Description                                 |
| ------------ | ------ | -------- | ------------------------------------------- |
| role         | string | Yes      | Role of the message ('user' or 'assistant') |
| content      | array  | Yes      | Content of the message                      |
| content.type | string | Yes      | Type of content ('text')                    |
| content.text | string | Yes      | The message text                            |

## Example: Sales Analysis Prompt

```php
use Automattic\WordpressMcp\Core\RegisterMcpPrompt;

class McpAnalyzeSales {
    public function __construct() {
        add_action('wordpress_mcp_init', [$this, 'register_prompt']);
    }

    public function register_prompt() {
        new RegisterMcpPrompt(
            [
                'name'        => 'analyze-sales',
                'description' => 'Analyze WooCommerce sales data',
                'arguments'   => [
                    [
                        'name'        => 'time_span',
                        'description' => 'The time period to analyze (e.g., last_7_days, last_30_days, last_month, last_quarter, last_year)',
                        'required'    => true,
                        'type'        => 'string',
                    ],
                ],
            ],
            $this->messages()
        );
    }

    public function messages() {
        return [
            [
                'role'    => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Analyze the WooCommerce sales data for the time period: {{time_span}}. Include total sales, average order value, top-selling products, and sales trends.',
                ],
            ],
        ];
    }
}
```

## Best Practices

1. **Unique Names**: Ensure prompt names are unique across all registered prompts
2. **Clear Descriptions**: Provide detailed descriptions of the prompt's purpose
3. **Well-Defined Arguments**: Clearly define all required and optional arguments
4. **Structured Messages**: Use consistent message structure and formatting
5. **Error Handling**: Include error handling in message templates
6. **Documentation**: Document the prompt's purpose and expected arguments
7. **Testing**: Test prompts with various argument combinations

## Example: Complete Prompt Implementation

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
                'description' => 'A custom prompt for AI interaction',
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

    private function messages() {
        return [
            [
                'role'    => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Process the following data: {{input_data}}. Format the output as {{format|default:"JSON"}}. If there are any issues, please explain them clearly.',
                ],
            ],
        ];
    }
}
```

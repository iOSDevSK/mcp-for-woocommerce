# WordPress MCP

[![Latest Release](https://img.shields.io/github/v/release/Automattic/wordpress-mcp)](https://github.com/Automattic/wordpress-mcp/releases)

A WordPress plugin that implements the Model Context Protocol (MCP) to expose WordPress functionality through a standardized interface. This plugin enables AI models and other applications to interact with WordPress sites in a structured and secure way.

## Features

-   🔒 Secure and standardized interface for WordPress interactions
-   🤖 AI-friendly API endpoints
-   🏗️ Extensible architecture for custom tools, resources and prompts
-   ⚡ High-performance implementation

## Installation

1. Download the latest wordpress-mcp.zip
   from https://github.com/Automattic/wordpress-mcp/releases/
2. Upload the plugin files to the `/wp-content/plugins/wordpress-mcp` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to `Settings > WordPress MCP` and enable MCP functionality and features

## Usage

This plugin is designed to work with [wp-wordpress-remote](https://github.com/Automattic/mcp-wordpress-remote), which provides the client-side implementation for interacting with the MCP interface. Please check the [usage instructions](https://github.com/Automattic/mcp-wordpress-remote?tab=readme-ov-file#usage)

## Development

### Extending the Plugin

You can extend the plugin's functionality by adding new components through the WordPress MCP API:

#### Adding New Tools

Check the tools defined on `wp-content/plugins/wordpress-mcp/includes/Tools/` for examples

#### Adding Resources

Check the resources define on `wp-content/plugins/wordpress-mcp/includes/Resources/` for examples

#### Adding Prompts

Check the prompts defined on `wp-content/plugins/wordpress-mcp/includes/Prompts/` for axamples

## Contributing

We welcome contributions!

## Support

For support, please:

-   Open an issue on GitHub
-   Contact the maintainers

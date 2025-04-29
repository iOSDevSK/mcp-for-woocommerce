# WordPress MCP

A WordPress plugin that implements the Model Context Protocol (MCP) to expose WordPress functionality through a standardized interface. This plugin enables AI models and other applications to interact with WordPress sites in a structured and secure way.

## Features

- 🔒 Secure and standardized interface for WordPress interactions
- 🤖 AI-friendly API endpoints
- 🏗️ Extensible architecture for custom tools, resources and prompts
- ⚡ High-performance implementation

## Installation

1. Download the plugin files
2. Upload the plugin files to the `/wp-content/plugins/wordpress-mcp` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to `Settings > WordPress MCP` and enable MCP functionality

## Usage

This plugin is designed to work with [wp-wordpress-remote-proxy](https://github.com/galatanovidiu/wp-wordpress-remote-proxy), which provides the client-side implementation for interacting with the MCP interface.

## Development

### Extending the Plugin

You can extend the plugin's functionality by adding new components:

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

- Open an issue on GitHub
- Check the [documentation](docs/)
- Contact the maintainers

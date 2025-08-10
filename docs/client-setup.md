# MCP Client Setup Guide

## About Woo MCP

Woo MCP is a specialized version of the [WordPress MCP](https://github.com/Automattic/wordpress-mcp) plugin by Automattic, specifically designed for WooCommerce integration. This plugin provides read-only access to publicly available WooCommerce store data, including products, categories, orders, and customer information.

The plugin is designed to work seamlessly with [Webtalkbot](https://webtalkbot.com), a platform that enables you to create intelligent WooCommerce AI chatbots that can be deployed directly on your WooCommerce-powered website. These chatbots can assist customers with product inquiries, product shipping, and Terms & Conditions using real-time data from your WooCommerce store.

All updates to the original WordPress MCP plugin that are relevant to WooCommerce functionality will be continuously integrated into Woo MCP to ensure compatibility and feature parity.

This guide explains how to connect various MCP clients to your Woo MCP server using different transport protocols and authentication methods.

## Overview

Woo MCP supports two transport protocols:

-   **STDIO Transport**: Traditional transport via `mcp-wordpress-remote` proxy
-   **Streamable Transport**: Direct HTTP-based transport with JSON-RPC 2.0

## Authentication Methods

### JWT Tokens (Recommended)

-   Generate tokens from `Settings > MCP > Authentication Tokens`
-   Tokens expire in 1-24 hours (configurable) or never
-   More secure than application passwords
-   Required for Streamable transport


## Client Configurations

### Claude Code

#### Using HTTP Transport with JWT Token (Recommended)

Add your Woo MCP server directly to Claude Code using the HTTP transport:

```bash
claude mcp add --transport http woo-mcp {{your-website.com}}/wp-json/wp/v2/wpmcp/streamable --header "Authorization: Bearer your-jwt-token-here"
```

For more information about Claude Code MCP configuration, see the [Claude Code MCP documentation](https://docs.anthropic.com/en/docs/claude-code/mcp).

### Claude Desktop

#### Using JWT Token with mcp-wordpress-remote (Recommended)

Add to your Claude Desktop `claude_desktop_config.json`:

```json
{
	"mcpServers": {
		"woo-mcp": {
			"command": "npx",
			"args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
			"env": {
				"WP_API_URL": "{{your-website.com}}",
				"JWT_TOKEN": "your-jwt-token-here"
			}
		}
	}
}
```


#### Local Development Configuration

To use with Claude Desktop for local development, add this configuration to your claude_desktop_config.json:

```json
{
	"mcpServers": {
		"woocommerce": {
			"command": "php",
			"args": [ "/path/to/your/woo-mcp/mcp-proxy.php" ]
		}
	}
}
```

### Cursor IDE

#### Using mcp-wordpress-remote proxy

Add to your Cursor MCP configuration file:

```json
{
	"mcpServers": {
		"woo-mcp": {
			"command": "npx",
			"args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
			"env": {
				"WP_API_URL": "{{your-website.com}}",
				"JWT_TOKEN": "your-jwt-token-here"
			}
		}
	}
}
```

### VS Code MCP Extension

#### Direct Streamable Transport (JWT Only)

Add to your VS Code MCP settings:

```json
{
	"servers": {
		"woo-mcp": {
			"type": "http",
			"url": "{{your-website.com}}/wp-json/wp/v2/wpmcp/streamable",
			"headers": {
				"Authorization": "Bearer your-jwt-token-here"
			}
		}
	}
}
```

### MCP Inspector (Development/Testing)

#### Using JWT Token with proxy

```bash
npx @modelcontextprotocol/inspector \
  -e WP_API_URL={{your-website.com}} \
  -e JWT_TOKEN=your-jwt-token-here \
  -e WOO_CUSTOMER_KEY=optional-woo-customer-key \
  -e WOO_CUSTOMER_SECRET=optional-woo-customer-secret \
  npx @automattic/mcp-wordpress-remote@latest
```

## Transport Protocol Details

### STDIO Transport

-   **Endpoint**: `/wp-json/wp/v2/wpmcp`
-   **Format**: WordPress-style REST API
-   **Authentication**: JWT tokens only
-   **Use Case**: Legacy compatibility, works with most MCP clients
-   **Proxy Required**: Yes (`mcp-wordpress-remote`)

#### Advantages:

-   Compatible with all MCP clients
-   Secure JWT authentication
-   Enhanced features via proxy (WooCommerce, logging)

#### Example Tools Available:

-   `wc_products_search` - Universal product search for ANY store type
-   `wc_get_product_variations` - Get all variations (colors, sizes, etc.) for a variable WooCommerce product
-   `wc_get_categories` - Get a specific product variation by ID
-   `wordpress_posts_get` - Get a single WordPress post by ID
-   `wordpress_pages_get` - Get a single WordPress page by ID
-   And many more...

### Streamable Transport

-   **Endpoint**: `/wp-json/wp/v2/wpmcp/streamable`
-   **Format**: JSON-RPC 2.0 compliant
-   **Authentication**: JWT tokens only
-   **Use Case**: Modern AI clients, direct integration
-   **Proxy Required**: No

#### Advantages:

-   Direct connection (no proxy needed)
-   Standard JSON-RPC 2.0 protocol
-   Lower latency
-   Modern implementation

#### Example Methods:

-   `tools/list` - List available tools
-   `tools/call` - Execute a tool
-   `resources/list` - List available resources
-   `resources/read` - Read resource content
-   `prompts/list` - List available prompts
-   `prompts/get` - Get prompt template

## Local Development Setup

### WordPress Local Environment

```json
{
	"mcpServers": {
		"wordpress-local": {
			"command": "node",
			"args": [ "/path/to/mcp-wordpress-remote/dist/proxy.js" ],
			"env": {
				"WP_API_URL": "http://localhost:8080/",
				"JWT_TOKEN": "your-local-jwt-token"
			}
		}
	}
}
```

## Troubleshooting

### Common Issues

#### JWT Token Expired

-   Generate a new token from WordPress admin
-   Check token expiration time in settings
-   Ensure system clock is synchronized

#### Authentication Failed

-   Verify JWT token is correctly copied
-   Ensure user has appropriate permissions
-   Check token expiration time

#### Connection Timeout

-   Verify WordPress site is accessible
-   Check firewall settings
-   Ensure proper SSL certificate if using HTTPS

#### Proxy Issues

-   Update mcp-wordpress-remote to latest version:
    ```bash
    npm install -g @automattic/mcp-wordpress-remote@latest
    ```
-   Check proxy logs for error details
-   Verify environment variables are set correctly

## Security Best Practices

1. **Use JWT tokens** instead of application passwords when possible
2. **Set appropriate expiration time** for your use case (1-24 hours or never)
3. **Revoke unused tokens** promptly from the admin interface
4. **Never commit tokens** to version control systems
5. **Use HTTPS** for production environments
6. **Regularly rotate tokens**

## Support

For additional help:

-   Check the [Woo MCP website](https://woomcp.dev)
-   Visit the [mcp-wordpress-remote repository](https://github.com/Automattic/mcp-wordpress-remote)
-   Report issues on [GitHub Issues](https://github.com/iOSDevSK/woo-mcp/issues)

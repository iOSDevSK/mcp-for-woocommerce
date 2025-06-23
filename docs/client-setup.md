# MCP Client Setup Guide

This guide explains how to connect various MCP clients to your WordPress MCP server using different transport protocols and authentication methods.

## Overview

WordPress MCP supports two transport protocols:

-   **STDIO Transport**: Traditional transport via `mcp-wordpress-remote` proxy
-   **Streamable Transport**: Direct HTTP-based transport with JSON-RPC 2.0

## Authentication Methods

### JWT Tokens (Recommended)

-   Generate tokens from `Settings > MCP > Authentication Tokens`
-   Tokens expire in 1-24 hours (configurable)
-   More secure than application passwords
-   Required for Streamable transport

### Application Passwords

-   WordPress built-in authentication method
-   Only works with STDIO transport via proxy
-   Generate from `Users > Profile > Application Passwords`

## Client Configurations

### Claude Code

#### Using HTTP Transport with JWT Token (Recommended)

Add your WordPress MCP server directly to Claude Code using the HTTP transport:

```bash
claude mcp add --transport http wordpress-mcp {{your-website.com}}/wp-json/wp/v2/wpmcp/streamable --header "Authorization: Bearer your-jwt-token-here"
```

For more information about Claude Code MCP configuration, see the [Claude Code MCP documentation](https://docs.anthropic.com/en/docs/claude-code/mcp).

### Claude Desktop

#### Using JWT Token with mcp-wordpress-remote (Recommended)

Add to your Claude Desktop `claude_desktop_config.json`:

```json
{
	"mcpServers": {
		"wordpress-mcp": {
			"command": "npx",
			"args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
			"env": {
				"WP_API_URL": "{{your-website.com}}",
				"JWT_TOKEN": "your-jwt-token-here",
				"LOG_FILE": "optional-path-to-log-file"
			}
		}
	}
}
```

#### Using Application Password with mcp-wordpress-remote

```json
{
	"mcpServers": {
		"wordpress-mcp": {
			"command": "npx",
			"args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
			"env": {
				"WP_API_URL": "{{your-website.com}}",
				"WP_API_USERNAME": "your-username",
				"WP_API_PASSWORD": "your-application-password",
				"LOG_FILE": "optional-full-path-to-log-file"
			}
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
		"wordpress-mcp": {
			"command": "npx",
			"args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
			"env": {
				"WP_API_URL": "{{your-website.com}}",
				"JWT_TOKEN": "your-jwt-token-here",
				"LOG_FILE": "optional-full-path-to-log-file"
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
		"wordpress-mcp": {
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
  -e LOG_FILE="optional-full-path-to-log-file"
  npx @automattic/mcp-wordpress-remote@latest
```

#### Using Application Password with proxy

```bash
npx @modelcontextprotocol/inspector \
  -e WP_API_URL={{your-website.com}} \
  -e WP_API_USERNAME=your-username \
  -e WP_API_PASSWORD=your-application-password \
  -e WOO_CUSTOMER_KEY=optional-woo-customer-key \
  -e WOO_CUSTOMER_SECRET=optional-woo-customer-secret \
  -e LOG_FILE="optional-full-path-to-log-file"
  npx @automattic/mcp-wordpress-remote@latest
```

## Transport Protocol Details

### STDIO Transport

-   **Endpoint**: `/wp-json/wp/v2/wpmcp`
-   **Format**: WordPress-style REST API
-   **Authentication**: JWT tokens OR Application passwords
-   **Use Case**: Legacy compatibility, works with most MCP clients
-   **Proxy Required**: Yes (`mcp-wordpress-remote`)

#### Advantages:

-   Compatible with all MCP clients
-   Supports both authentication methods
-   Enhanced features via proxy (WooCommerce, logging)

#### Example Tools Available:

-   `wp_get_posts` - Retrieve WordPress posts
-   `wp_create_post` - Create new posts
-   `wp_update_post` - Update existing posts
-   `wp_get_users` - Get user information
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
				"JWT_TOKEN": "your-local-jwt-token",
				"LOG_FILE": "/tmp/wordpress-mcp-local.log"
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
-   Check application password format (username:password)
-   Ensure user has appropriate permissions

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
2. **Set shortest expiration time** needed for your use case (1-24 hours)
3. **Revoke unused tokens** promptly from the admin interface
4. **Never commit tokens** to version control systems
5. **Use HTTPS** for production environments
6. **Regularly rotate tokens**

## Support

For additional help:

-   Check the [WordPress MCP documentation](https://github.com/Automattic/wordpress-mcp)
-   Visit the [mcp-wordpress-remote repository](https://github.com/Automattic/mcp-wordpress-remote)
-   Report issues on [GitHub Issues](https://github.com/Automattic/wordpress-mcp/issues)

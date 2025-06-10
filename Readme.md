# WordPress MCP

[![Latest Release](https://img.shields.io/github/v/release/Automattic/wordpress-mcp)](https://github.com/Automattic/wordpress-mcp/releases)

A comprehensive WordPress plugin that implements the [Model Context Protocol (MCP)](https://modelcontextprotocol.io) to expose WordPress functionality through standardized interfaces. This plugin enables AI models and applications to interact with WordPress sites securely using multiple transport protocols and enterprise-grade authentication.

## ✨ Features

-   🔄 **Dual Transport Protocols**: STDIO and HTTP-based (Streamable) transports
-   🔐 **JWT Authentication**: Secure token-based authentication with management UI
-   🎛️ **Admin Interface**: React-based token management and settings dashboard
-   🤖 **AI-Friendly APIs**: JSON-RPC 2.0 compliant endpoints for AI integration
-   🏗️ **Extensible Architecture**: Custom tools, resources, and prompts support
-   🔌 **WordPress Feature API**: Adapter for standardized WordPress functionality
-   🧪 **Comprehensive Testing**: 200+ test cases covering all protocols and authentication
-   ⚡ **High Performance**: Optimized routing and caching mechanisms
-   🔒 **Enterprise Security**: Multi-layer authentication and audit logging

## 🏗️ Architecture

The plugin implements a dual transport architecture:

```
WordPress MCP Plugin
├── Transport Layer
│   ├── McpStdioTransport (/wp/v2/wpmcp)
│   └── McpStreamableTransport (/wp/v2/wpmcp/streamable)
├── Authentication
│   └── JWT Authentication System
├── Method Handlers
│   ├── Tools, Resources, Prompts
│   └── System & Initialization
└── Admin Interface
    └── React-based Token Management
```

### Transport Protocols

| Protocol       | Endpoint                  | Format          | Authentication      | Use Case             |
| -------------- | ------------------------- | --------------- | ------------------- | -------------------- |
| **STDIO**      | `/wp/v2/wpmcp`            | WordPress-style | JWT + App Passwords | Legacy compatibility |
| **Streamable** | `/wp/v2/wpmcp/streamable` | JSON-RPC 2.0    | JWT only            | Modern AI clients    |

## 🚀 Installation

### Quick Install

1. Download `wordpress-mcp.zip` from [releases](https://github.com/Automattic/wordpress-mcp/releases/)
2. Upload to `/wp-content/plugins/wordpress-mcp` directory
3. Activate through WordPress admin 'Plugins' menu
4. Navigate to `Settings > WordPress MCP` to configure

### Composer Install (Development)

```bash
cd wp-content/plugins/
git clone https://github.com/Automattic/wordpress-mcp.git
cd wordpress-mcp
composer install --no-dev
npm install && npm run build
```

## 🔐 Authentication Setup

### JWT Token Generation

1. Go to `Settings > WordPress MCP > Authentication Tokens`
2. Select token duration (1-24 hours)
3. Click "Generate New Token"
4. Copy the token for use in your MCP client

### MCP Client Configuration

#### Claude Desktop Configuration using [mcp-wordpress-remote](https://github.com/Automattic/mcp-wordpress-remote) proxy

Add to your Claude Desktop `claude_desktop_config.json`:

```json
{
	"mcpServers": {
		"wordpress-mcp": {
			"command": "npx",
			"args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
			"env": {
				"WP_API_URL": "https://your-site.com/",
				"JWT_TOKEN": "your-jwt-token-here",
				"LOG_FILE": "optional-path-to-log-file"
			}
		}
	}
}
```

#### Using Application Passwords (Alternative)

```json
{
	"mcpServers": {
		"wordpress-mcp": {
			"command": "npx",
			"args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
			"env": {
				"WP_API_URL": "https://your-site.com/",
				"WP_API_USERNAME": "your-username",
				"WP_API_PASSWORD": "your-application-password",
				"LOG_FILE": "optional-path-to-log-file"
			}
		}
	}
}
```

#### VS Code MCP Extension (Direct Streamable Transport)

Add to your VS Code MCP settings:

```json
{
	"servers": {
		"wordpress-mcp": {
			"type": "http",
			"url": "https://your-site.com/wp-json/wp/v2/wpmcp/streamable",
			"headers": {
				"Authorization": "Bearer your-jwt-token-here"
			}
		}
	}
}
```

#### MCP Inspector (Development/Testing)

```bash
# Using JWT Token with proxy
npx @modelcontextprotocol/inspector \
  -e WP_API_URL=https://your-site.com/ \
  -e JWT_TOKEN=your-jwt-token-here \
  npx @automattic/mcp-wordpress-remote@latest

# Using Application Password with proxy
npx @modelcontextprotocol/inspector \
  -e WP_API_URL=https://your-site.com/ \
  -e WP_API_USERNAME=your-username \
  -e WP_API_PASSWORD=your-application-password \
  npx @automattic/mcp-wordpress-remote@latest
```

#### Local Development Configuration

```json
{
	"mcpServers": {
		"wordpress-local": {
			"command": "node",
			"args": [ "/path/to/mcp-wordpress-remote/dist/proxy.js" ],
			"env": {
				"WP_API_URL": "http://localhost:8080/",
				"JWT_TOKEN": "your-local-jwt-token",
				"LOG_FILE": "optional-path-to-log-file"
			}
		}
	}
}
```

## 🎯 Usage

### With MCP Clients

This plugin works seamlessly with MCP-compatible clients in two ways:

**Via Proxy:**

-   [mcp-wordpress-remote](https://github.com/Automattic/mcp-wordpress-remote) - Official MCP client with enhanced features
-   Claude Desktop with proxy configuration for full WordPress and WooCommerce support
-   Any MCP client using the STDIO transport protocol

**Direct Streamable Transport:**

-   VS Code MCP Extension connecting directly to `/wp/v2/wpmcp/streamable`
-   Custom HTTP-based MCP implementations using JSON-RPC 2.0
-   Any client supporting HTTP transport with JWT authentication

The streamable transport provides a direct JSON-RPC 2.0 compliant endpoint, while the proxy offers additional features like WooCommerce integration, enhanced logging, and compatibility with legacy authentication methods.

### Available MCP Methods

| Method           | Description              | Transport Support |
| ---------------- | ------------------------ | ----------------- |
| `initialize`     | Initialize MCP session   | Both              |
| `tools/list`     | List available tools     | Both              |
| `tools/call`     | Execute a tool           | Both              |
| `resources/list` | List available resources | Both              |
| `resources/read` | Read resource content    | Both              |
| `prompts/list`   | List available prompts   | Both              |
| `prompts/get`    | Get prompt template      | Both              |

## 🔧 Development

### Project Structure

```
wp-content/plugins/wordpress-mcp/
├── includes/                   # PHP classes
│   ├── Core/                  # Transport and core logic
│   ├── Auth/                  # JWT authentication
│   ├── Tools/                 # MCP tools
│   ├── Resources/             # MCP resources
│   ├── Prompts/               # MCP prompts
│   └── Admin/                 # Settings interface
├── src/                       # React components
│   └── settings/              # Admin UI components
├── tests/                     # Test suite
│   └── phpunit/              # PHPUnit tests
└── docs/                      # Documentation
```

### Adding Custom Tools

You can extend the MCP functionality by adding custom tools through your own plugins or themes. Create a new tool class in your plugin or theme:

```php
<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Tools;

class MyCustomTool {
    public function register(): void {
        add_action('wp_mcp_register_tools', [$this, 'register_tool']);
    }

    public function register_tool(): void {
        WPMCP()->register_tool([
            'name' => 'my_custom_tool',
            'description' => 'My custom tool description',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'param1' => ['type' => 'string', 'description' => 'Parameter 1']
                ],
                'required' => ['param1']
            ],
            'callback' => [$this, 'execute'],
        ]);
    }

    public function execute(array $args): array {
        // Your tool logic here
        return ['result' => 'success'];
    }
}
```

### Adding Custom Resources

You can extend the MCP functionality by adding custom resources through your own plugins or themes. Create a new resource class in your plugin or theme:

```php
<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Resources;

class MyCustomResource {
    public function register(): void {
        add_action('wp_mcp_register_resources', [$this, 'register_resource']);
    }

    public function register_resource(): void {
        WPMCP()->register_resource([
            'uri' => 'custom://my-resource',
            'name' => 'My Custom Resource',
            'description' => 'Custom resource description',
            'mimeType' => 'application/json',
            'callback' => [$this, 'get_content'],
        ]);
    }

    public function get_content(): array {
        return ['contents' => [/* resource data */]];
    }
}
```

### Testing

Run the comprehensive test suite:

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suites
vendor/bin/phpunit tests/phpunit/McpStdioTransportTest.php
vendor/bin/phpunit tests/phpunit/McpStreamableTransportTest.php
vendor/bin/phpunit tests/phpunit/JwtAuthTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Building Frontend

```bash
# Development build
npm run dev

# Production build
npm run build

# Watch mode
npm run start
```

## 🔒 Security

### Best Practices

-   **Token Management**: Use shortest expiration time needed (1-24 hours)
-   **User Permissions**: Tokens inherit user capabilities
-   **Secure Storage**: Never commit tokens to repositories
-   **Regular Cleanup**: Revoke unused tokens promptly
-   **Access Control**: Streamable transport requires admin privileges

### Security Features

-   ✅ JWT signature validation
-   ✅ Token expiration and revocation
-   ✅ User capability inheritance
-   ✅ Secure secret key generation
-   ✅ Audit logging for security events
-   ✅ Protection against malformed requests

## 📊 Testing Coverage

The plugin includes extensive testing:

-   **Transport Testing**: Both STDIO and Streamable protocols
-   **Authentication Testing**: JWT generation, validation, and revocation
-   **Integration Testing**: Cross-transport comparison
-   **Security Testing**: Edge cases and malformed requests
-   **Performance Testing**: Load and stress testing

View detailed testing documentation in [`tests/README.md`](tests/README.md).

## 🔧 Configuration

### Environment Variables

```php
// wp-config.php
define('WPMCP_JWT_SECRET_KEY', 'your-secret-key');
define('WPMCP_DEBUG', true); // Enable debug logging
```

### Plugin Settings

Access via `Settings > WordPress MCP`:

-   **Enable/Disable MCP**: Toggle plugin functionality
-   **Transport Configuration**: Configure STDIO/Streamable transports
-   **Feature Toggles**: Enable/disable specific tools and resources
-   **Authentication Settings**: JWT token management

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md).

### Development Setup

1. Clone the repository
2. Run `composer install` for PHP dependencies
3. Run `npm install` for JavaScript dependencies
4. Set up WordPress test environment
5. Run tests with `vendor/bin/phpunit`

## 📚 Documentation

-   **API Reference**: [docs/api/](docs/api/)
-   **Architecture Guide**: [docs/architecture.md](docs/architecture.md)
-   **Security Guide**: [docs/security.md](docs/security.md)
-   **Testing Guide**: [tests/README.md](tests/README.md)

## 🆘 Support

For support and questions:

-   📖 **Documentation**: [docs/README.md](docs/README.md)
-   🐛 **Bug Reports**: [GitHub Issues](https://github.com/Automattic/wordpress-mcp/issues)
-   💬 **Discussions**: [GitHub Discussions](https://github.com/Automattic/wordpress-mcp/discussions)
-   ✉️ **Contact**: Reach out to the maintainers

## 📄 License

This project is licensed under the [GPL v2 or later](LICENSE).

---

Built with ❤️ by [Automattic](https://automattic.com) for the WordPress and AI communities.

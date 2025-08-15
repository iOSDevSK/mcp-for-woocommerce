=== Woo MCP - WordPress & WooCommerce AI Integration ===
Contributors: filipdvoran, claude, automattic, ovidiu-galatan
Tags: ai, mcp, woocommerce, chatbot, ecommerce
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI integration plugin connecting WooCommerce & WordPress with Model Context Protocol for seamless AI assistant interactions.

== Description ==

Woo MCP is a comprehensive WordPress plugin that bridges your WooCommerce store and WordPress site with AI assistants through the Model Context Protocol (MCP). This plugin provides a standardized interface for AI assistants to access and interact with your e-commerce data in a secure, read-only manner.

**Key Features:**

* **MCP Server Implementation** - Full Model Context Protocol server with tools, resources, and prompts
* **WooCommerce Integration** - Access products, orders, categories, reviews, shipping, and payment data
* **WordPress Content Access** - Retrieve posts, pages, media, and site information
* **Secure Authentication** - JWT-based authentication with configurable access controls
* **Multiple Transport Methods** - STDIO and HTTP streamable transports
* **AI-Ready Interface** - Optimized for Claude, ChatGPT, and other AI assistants
* **Read-Only Safety** - All operations are read-only to ensure data security
* **Intelligent Search** - Advanced search capabilities for products and content
* **Comprehensive Documentation** - Built-in guides and examples

**What is MCP?**

Model Context Protocol (MCP) is an open standard that enables AI assistants to securely access external data sources and tools. This plugin acts as an MCP server, allowing AI assistants to understand and interact with your WordPress/WooCommerce site through standardized interfaces.

**Use Cases:**

* Customer service automation with AI chatbots
* Product recommendation engines
* Content management assistance
* Sales analytics and reporting
* Inventory management support
* Site administration helpers

**Requirements:**

* WordPress 6.4 or higher
* PHP 8.0 or higher
* WooCommerce plugin (for e-commerce features)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woo-mcp` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure WooCommerce is installed and activated for e-commerce features.
4. Navigate to Settings → Woo MCP to configure the plugin.
5. Generate JWT tokens for secure access or configure public access as needed.
6. Follow the connection examples provided in the settings page to connect your AI assistant.

== Frequently Asked Questions ==

= Is this plugin safe to use? =

Yes, all MCP tools and resources are read-only. The plugin cannot modify, create, or delete any data on your site. It only provides access to existing public information.

= Which AI assistants work with this plugin? =

Any AI assistant that supports the Model Context Protocol (MCP) can work with this plugin, including Claude Desktop, VS Code with MCP extensions, and custom MCP clients.

= Do I need technical knowledge to use this plugin? =

Basic WordPress administration knowledge is sufficient. The plugin provides clear documentation and examples for connecting AI assistants.

= What data can AI assistants access? =

AI assistants can access the same public data that would be available through your site's REST API, including products, posts, pages, categories, and basic site information. No private or sensitive data is exposed.

= Can I control what data is accessible? =

Yes, the plugin includes various settings to control access levels and configure authentication requirements.

= Does this plugin slow down my website? =

No, the plugin only activates when specifically called by an MCP client. It has no impact on your regular website performance.

== Screenshots ==

1. Settings overview page with connection examples
2. JWT token management interface
3. Available tools and resources documentation
4. Real-time connection testing interface

== Changelog ==

= 1.1.5 =
* Enhanced intelligent search capabilities
* Improved JWT authentication handling
* Added comprehensive documentation
* Better error handling and logging
* Updated MCP protocol compatibility

= 1.1.4 =
* Added streamable HTTP transport
* Enhanced WooCommerce integration
* Improved security measures
* Better plugin compatibility

= 1.1.3 =
* Added product search and filtering
* Enhanced order management tools
* Improved resource documentation
* Bug fixes and performance improvements

= 1.1.2 =
* Initial WooCommerce MCP server implementation
* JWT authentication system
* STDIO transport support
* Basic tools and resources

= 1.1.1 =
* Beta release with core MCP functionality

= 1.1.0 =
* Initial alpha release

== Upgrade Notice ==

= 1.1.5 =
Latest stable release with enhanced search capabilities and improved documentation. Recommended for all users.

== Additional Information ==

**Support:** For technical support and questions, please visit our GitHub repository or contact support through the WordPress.org forums.

**Documentation:** Comprehensive documentation is available within the plugin settings page and in our online documentation.

**Contributing:** This plugin is open source. Contributions are welcome through our GitHub repository.

**Privacy:** This plugin does not collect or transmit any personal data. All interactions are between your site and your configured AI assistants.
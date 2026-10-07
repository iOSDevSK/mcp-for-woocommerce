=== MCP for WooCommerce ===
Contributors: webtalkbot
Tags: ai, mcp, woocommerce, chatbot, ecommerce
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI integration plugin connecting WooCommerce & WordPress with Model Context Protocol for seamless AI assistant interactions.

== Description ==

MCP for WooCommerce turns your store into a read-only Model Context Protocol (MCP) server. AI assistants such as Claude, ChatGPT, Cursor or VS Code can connect to it and answer questions about your catalogue, using the same information a shop visitor already sees on your storefront.

This is a community-developed plugin. It is not an official WooCommerce or WordPress plugin and is not affiliated with Automattic.

**What AI assistants can read**

* **Products** – published products, prices, sale prices, stock status, variations, images and product links
* **Catalogue structure** – categories, tags, brands and attributes
* **Reviews** – approved product reviews (the reviewer's name, never their e-mail address)
* **Shipping and taxes** – shipping zones, enabled shipping methods and their costs, tax classes and rates
* **Payment methods** – the payment methods offered at checkout
* **Posts and pages** – published WordPress posts and pages
* **Smart product search** – natural-language search that understands price ranges, brands, categories and attributes

**What it never does**

* It never creates, changes or deletes anything. Every tool is read-only.
* It never signs in as a WordPress user and has no administrator access.
* It never returns orders, customers, users, e-mail addresses, settings, or draft, private or password-protected content.

**How access works**

The MCP endpoint is public while MCP is enabled, the same way the WooCommerce Store API and the public WordPress REST API are. Because it only returns storefront information, no account or token is needed. You can switch the whole endpoint off, or turn individual tools off, from the settings page.

**What is MCP?**

The Model Context Protocol is an open standard that lets AI assistants call tools and read data from external systems. This plugin is an MCP server: it describes your store's read-only tools so an AI assistant can use them.

**Use cases**

* A shopping assistant or AI chatbot that recommends products and links straight to them
* Answering customer questions about products, shipping and payment options
* Exploring your catalogue in Claude, ChatGPT, Cursor or VS Code

**Requirements**

* WordPress 6.4 or higher
* PHP 8.0 or higher
* WooCommerce

== Installation ==

1. Install the plugin from the Plugins screen, or upload it to `/wp-content/plugins/mcp-for-woocommerce`.
2. Make sure WooCommerce is installed and active, then activate the plugin.
3. Go to Settings → MCP for WooCommerce and switch on **Enable MCP functionality**.
4. Set Settings → Permalinks to **Post name** so product links work.
5. Copy your MCP endpoint (`https://your-site.com/wp-json/wp/v2/wpmcp/streamable`) into your AI assistant. The Documentation tab has ready-made configuration for Claude Code, Claude Desktop, Cursor and VS Code.

== Frequently Asked Questions ==

= Is this plugin safe to use? =

Yes. Every tool is read-only, and the plugin only returns information that is already public on your storefront. It cannot create, change or delete anything on your site, and it never signs in as a WordPress user.

= Why does the endpoint not need a password or token? =

It only serves public storefront information, the same as your shop pages and the WooCommerce Store API. Orders, customers, users, settings and unpublished content are never exposed, so there is nothing to protect with a login.

= Can I turn access off? =

Yes. Switch off **Enable MCP functionality** to disable the endpoint completely, or turn individual tools off on the Tools tab.

= Which AI assistants work with this plugin? =

Any client that supports the Model Context Protocol over Streamable HTTP, including Claude Code, Claude Desktop (through `mcp-remote`), Cursor, VS Code and the MCP Inspector.

= Does the plugin contact external services? =

No. The plugin does not send any data to external services. AI assistants connect to your site; your site does not connect to them.

= Does this plugin slow down my website? =

No. It only runs when an MCP client calls the endpoint.

== Changelog ==

= 1.3.0 =
* The MCP endpoint is now public and strictly read-only, and it only returns storefront information: published products, catalogue structure, approved reviews, shipping, tax and payment options, and published posts and pages
* Removed JWT and OAuth authentication. The plugin no longer issues tokens or signs in as a WordPress user
* SECURITY: Posts and pages tools no longer accept a status filter, so draft, private and password-protected content can no longer be requested
* SECURITY: Reviews no longer include the reviewer's e-mail address, and posts and pages no longer include the author's login or e-mail address
* SECURITY: Products that are not published or are hidden from the catalogue are no longer returned
* Payment and shipping tools return only what checkout shows, not gateway or shipping method settings
* Removed the system status tool, the OpenAPI document, and unused code for tools that were never registered
* The plugin no longer generates a proxy script in the uploads directory; an existing one is deleted on update
* Removed the firebase/php-jwt dependency

= 1.2.5 =
* Confirm compatibility with WordPress 7.1

= 1.2.4 =
* Confirm compatibility with WordPress 7.0
* Correct the Contributors field to a valid WordPress.org username, and remove a Screenshots section that had no matching assets
* SECURITY: The OAuth authorization form submission accepted any client_id and redirect_uri without validation, so a crafted form could capture an authorization code on an attacker-controlled host. Both are now validated against the registered client before credentials are processed.
* Harden the tax rate query so every SQL placeholder is visible to static analysis, and cache results in the object cache
* Serve /.well-known/oauth-authorization-server with a 200 status instead of 404, and stop writing a static copy into the web root on activation
* Pass the WordPress.org Plugin Check with zero errors and zero warnings
* Remove set_time_limit() and ini_set() calls, and delete unused proxy code
* Move client-setup.md into documentation/ and drop the command-line proxy script from the distributed package

= 1.2.3 =
* Fix Streamable HTTP transport writing HTTP chunk framing into the JSON body, which made every JSON-RPC response unparseable for spec-compliant MCP clients (issue #5)
* Responses are now a single complete application/json body with a correct Content-Length; the plugin no longer sets Transfer-Encoding or Connection headers
* Fix Mcp-Session-Id header being silently dropped for batches of more than 5 messages
* Notification-only requests now return an empty 202 body instead of stray framing bytes
* Remove references to a decommissioned demo host from the setup docs; mcp-proxy.php now takes the endpoint URL as an argument instead of hard-coding one

= 1.2.2 =
* Fix "Unable to generate new token" error when jwt-authentication-for-wp-rest-api plugin is active (issue #3)
* Move all JWT auth REST routes from `jwt-auth/v1/*` to plugin-specific `mcpfowo/v1/auth/*` namespace to avoid collisions
* Update OAuth discovery endpoint URLs to the new namespace

= 1.2.1 =
* Added OAuth 2.0 Authorization Code Flow with PKCE support
* Implemented dynamic client registration for MCP clients
* Added custom authorization form for seamless OAuth flow
* Enhanced JWT authentication to accept tokens with or without Bearer prefix
* Automatic OAuth discovery endpoint creation on plugin activation
* Added activation/deactivation hooks for automated setup
* Improved Claude Code compatibility with OAuth-compliant error responses
* Token endpoint now returns OAuth-standard response format (access_token, token_type)

= 1.2.0 =
* Enhanced JWT authentication system
* Improved security and token management
* Better MCP client compatibility

= 1.1.9 =
* Fix missing build files in WordPress.org distribution
* Ensure React admin UI loads correctly after installation

= 1.1.8 =
* Apply WordPress.org review requirements
* Improve boolean settings storage consistency
* Resolve admin UI rendering issues
* Update documentation for WordPress.org submission

= 1.1.7 =
* Rebranded as community plugin independent from Automattic
* Updated author information to Filip Dvoran only
* Updated all repository links to community GitHub repo
* Added community plugin disclaimers throughout documentation
* Clarified plugin independence in all descriptions

= 1.1.6 =
* Enhanced build scripts with version auto-detection
* Improved stable tag handling in readme

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

= 1.3.0 =
Authentication was removed: the MCP endpoint is now public and read-only and serves storefront information only. Existing tokens stop working; reconnect clients with the endpoint URL alone.

= 1.1.7 =
Community plugin release with rebranding and updated author information. All functionality remains the same.

= 1.1.5 =
Latest stable release with enhanced search capabilities and improved documentation. Recommended for all users.

== Additional Information ==

**Support:** For technical support and questions, please visit our GitHub repository or contact support through the WordPress.org forums.

**Documentation:** Comprehensive documentation is available within the plugin settings page and in our online documentation.

**Contributing:** This plugin is open source. Contributions are welcome through our GitHub repository.

**Privacy:** This plugin does not collect, store or transmit personal data. It never returns customer, order or user information, and it sends nothing to external services.
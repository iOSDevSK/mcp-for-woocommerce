# MCP for WooCommerce

[![Latest Release](https://img.shields.io/github/v/release/iOSDevSK/mcp-for-woocommerce)](https://github.com/iOSDevSK/mcp-for-woocommerce/releases) [![License](https://img.shields.io/github/license/iOSDevSK/mcp-for-woocommerce)](https://github.com/iOSDevSK/mcp-for-woocommerce/blob/main/LICENSE) [![GitHub Stars](https://img.shields.io/github/stars/iOSDevSK/mcp-for-woocommerce?style=social)](https://github.com/iOSDevSK/mcp-for-woocommerce/stargazers)

**Connect your WooCommerce store to AI assistants like Claude and VS Code.** This WordPress plugin enables AI clients to access your store's product catalog, categories, reviews, and content through a secure, read-only interface.

> **Community Plugin Notice**: This is a community-developed plugin and is not affiliated with or endorsed by Automattic, the creators of WordPress and WooCommerce. While it builds upon the foundation of the official WordPress MCP implementation, this plugin is independently maintained.

[MCP for WooCommerce](https://mcpforwoocommerce.com) transforms your WordPress site into an AI-accessible data source built on [Automattic's official WordPress MCP](https://github.com/Automattic/wordpress-mcp). It safely exposes public store information—products, categories, tags, reviews, shipping options, and WordPress content—while protecting customer data and private details.

Perfect for building AI-powered shopping assistants or integrating with custom AI applications.

## Key Features

- Read-only access: all tools are type "read" (no writes)
- Product/variation permalinks: every product/variation includes a `permalink` field (must be shown in AI responses)
- Dual transports: STDIO (WordPress style) and Streamable HTTP (JSON-RPC 2.0)
- JWT authentication: secure token access, with optional local-development mode
- Admin UI: settings page with tool toggles and automated proxy generation for Claude Desktop when JWT is disabled
- WooCommerce focus: intelligent search, categories, tags, attributes, reviews, shipping, payments, taxes, system status
- WordPress content: posts and pages with permalinks

## Why Choose MCP for WooCommerce

- WooCommerce MCP Server: turnkey MCP server for WooCommerce + WordPress.
- WordPress MCP Plugin: install, toggle tools, authenticate, and connect any MCP client.
- AI Chatbot/Agent: integrate with chat platforms to deploy on-site assistance in minutes.
- Read-only and safe: no PII; tools return permalinks for clickable product links.
- Works with Claude, VS Code MCP, MCP Inspector, custom MCP clients.

## Architecture and Endpoints

- STDIO transport (WordPress format)
  - Endpoint: `/wp-json/wp/v2/wpmcp`
  - Auth: JWT if required, or unauthenticated read-only if JWT is disabled in settings
  - Usage: broad client compatibility via `@automattic/mcp-wordpress-remote` proxy

- Streamable HTTP transport (JSON-RPC 2.0)
  - Endpoint: `/wp-json/wp/v2/wpmcp/streamable`
  - Auth: JWT (recommended for direct integration)
  - Benefits: lower latency, no proxy needed, modern MCP
  - OpenAPI: `/wp-json/wp/v2/wpmcp/openapi.json`

Tip: If you’re searching for “WooCommerce MCP Server endpoint”, this is it. Use the Streamable HTTP transport for modern, low-latency clients.

## Requirements

- WordPress 6.4+
- PHP 8.0+
- WooCommerce activated
- Node.js (admin UI build), Composer (development)

## Installation

1) WordPress Admin (recommended)
- Download the latest release, upload ZIP via Plugins > Add New > Upload
- Activate the plugin

2) Manual
- Upload ZIP to `wp-content/plugins/`
- Extract and activate in Plugins

3) Development install
```
cd wp-content/plugins/
git clone https://github.com/Automattic/wordpress-mcp.git mcp-for-woocommerce
cd mcp-for-woocommerce
composer install
npm install && npm run build
```

## AI Shopping Assistant

Deploy AI-powered customer assistance on your site using the MCP data interface.

Benefits:
- Direct WooCommerce data access via standardized tools
- Secure authentication with JWT tokens  
- Product information with clickable links
- Compatible with various AI platforms

Setup process:
1) Configure your AI platform to use the MCP endpoint
2) Copy a JWT token from WordPress Admin → Settings → MCP for WooCommerce → Tokens
3) Deploy your chosen chat interface or assistant

Result: an AI assistant connected to your catalog that can answer questions with product links and variations.

## Documentation Site

- Browse the documentation site (after GitHub Pages is enabled): `https://iosdevsk.github.io/mcp-for-woocommerce/`
- Quick links:
  - What is WooCommerce MCP Server? `docs/woocommerce-mcp-server/what-is.md`
  - Install: `docs/woocommerce-mcp-server/install.md`
  - Configure: `docs/woocommerce-mcp-server/configure.md`
  - Examples: `docs/woocommerce-mcp-server/examples.md`
  - Troubleshooting: `docs/woocommerce-mcp-server/troubleshooting.md`
  - FAQ: `docs/woocommerce-mcp-server/faq.md`

## Admin Settings

- Location: Settings > MCP for WooCommerce
- Core toggles:
  - Enable MCP functionality: master on/off for the plugin
  - Require JWT Authentication: enforce JWT for MCP endpoints
    - When disabled, the plugin can act as a local Claude Desktop connector. It automatically generates a proxy script file.
- Tools: enable/disable individual tools (states stored in the `wordpress_mcp_tool_states` option)

Note: The settings page is a React UI (assets in `build/`).

## Authentication and Clients

- JWT tokens
  - Generate/manage from the admin UI (Authentication Tokens)
  - Best practice: rotate tokens, use short expirations for production

- Claude Code (direct HTTP + JWT)
```
claude mcp add --transport http \
  mcp-for-woocommerce https://your-site.com/wp-json/wp/v2/wpmcp/streamable \
  --header "Authorization: Bearer YOUR_JWT"
```

- Claude Desktop via proxy (recommended for STDIO)
```
{
  "mcpServers": {
    "mcp-for-woocommerce": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://your-site.com",
        "JWT_TOKEN": "YOUR_JWT"
      }
    }
  }
}
```

- VS Code MCP Extension (direct Streamable + JWT)
```
{
  "servers": {
    "mcp-for-woocommerce": {
      "type": "http",
      "url": "https://your-site.com/wp-json/wp/v2/wpmcp/streamable",
      "headers": { "Authorization": "Bearer YOUR_JWT" }
    }
  }
}
```

- MCP Inspector (testing)
```
npx @modelcontextprotocol/inspector \
  -e WP_API_URL=https://your-site.com \
  -e JWT_TOKEN=YOUR_JWT \
  npx @automattic/mcp-wordpress-remote@latest
```

### Automatic local proxy generation (when JWT is disabled)

- When you toggle “Require JWT Authentication” OFF in Settings > MCP for WooCommerce, the plugin automatically generates a Claude Desktop-friendly MCP proxy script at:
  - `wp-content/plugins/mcp-for-woocommerce/mcp-proxy.js` (executable, Node.js)
- The UI also surfaces ready-to-copy Claude Desktop config JSON. Additionally, a PHP proxy file ships with the plugin (`mcp-proxy.php`) if you prefer PHP:
```
// Node (uses generated mcp-proxy.js)
{
  "mcpServers": {
    "woocommerce": {
      "command": "node",
      "args": ["/wp-content/plugins/mcp-for-woocommerce/mcp-proxy.js"]
    }
  }
}

// PHP (ships with the plugin)
{
  "mcpServers": {
    "woocommerce": {
      "command": "php",
      "args": ["/wp-content/plugins/mcp-for-woocommerce/mcp-proxy.php"]
    }
  }
}
```

## Best-Practice Product Search Workflow

1. Use `wc_products_search` first to find products by name/description
2. Use `wc_get_product` with the returned ID for details
3. Use `wc_get_product_variations` (or `wc_get_product_variation`) for variations
4. Always include clickable `permalink` links for products and variations

## Registered Tools (read-only)

- Products & search
  - `wc_products_search` — primary universal search (includes `permalink`)
  - `wc_get_product` — product by ID (includes `permalink`)
  - `wc_get_product_variations` — all variations for a variable product (each includes `permalink`)
  - `wc_get_product_variation` — specific variation by ID (includes `permalink`)
  - `wc_intelligent_search` — intelligent fallback multi-stage search
  - `wc_analyze_search_intent` — analyze user query and suggest parameters
  - `wc_analyze_search_intent_helper` — helper for categories/tags mapping
  - `wc_get_products_by_brand` — products by brand (attribute/category/custom taxonomy)
  - `wc_get_products_by_category` — products by category
  - `wc_get_products_by_attributes` — products filtered by attributes
  - `wc_get_products_filtered` — multi-criteria filtering (brand/category/price/attributes)

- Categories, tags, attributes
  - `wc_get_categories` — list product categories
  - `wc_get_tags` — list product tags
  - `wc_get_product_attributes` — global attribute definitions
  - `wc_get_product_attribute` — attribute by ID
  - `wc_get_attribute_terms` — attribute terms (e.g., Red, Blue for Color)

- Reviews
  - `wc_get_product_reviews` — list reviews with filters/pagination
  - `wc_get_product_review` — single review by ID

- Shipping & payments
  - `wc_get_shipping_zones`, `wc_get_shipping_zone`
  - `wc_get_shipping_methods`, `wc_get_shipping_locations`
  - `wc_get_payment_gateways`, `wc_get_payment_gateway`

- Taxes & system
  - `wc_get_tax_classes`, `wc_get_tax_rates`
  - `wc_get_system_status`, `wc_get_system_tools`

- WordPress content
  - `wordpress_posts_list`, `wordpress_posts_get`
  - `wordpress_pages_list`, `wordpress_pages_get`

Notes:
- Tools are defined under `includes/Tools/*` and gated by WooCommerce where applicable.
- Some analytics/report helpers are available as REST aliases (read-only).


## Integration Examples

Connect various AI platforms to your WooCommerce data.

Prerequisites:
- MCP for WooCommerce installed and enabled
- JWT token generated in WordPress Admin (Settings > MCP for WooCommerce)
- Your chosen AI platform or chatbot service

Common integration patterns:

**API Endpoint Integration:**
- Use streamable endpoint: `/wp-json/wp/v2/wpmcp/streamable`
- Include JWT token in Authorization header
- Follow JSON-RPC 2.0 protocol for requests

**Chat Platform Setup:**
- Configure platform to use MCP endpoint  
- Provide your WordPress site URL
- Authenticate with generated JWT token
- Example platforms: [Webtalkbot](https://webtalkbot.com), custom chatbots, AI assistants

**Best practices:**
- Test with common customer questions
- Ensure product links are included in responses
- Monitor API usage and performance
- Keep JWT tokens secure and rotate regularly

## Security

- JWT: signature validation, expiration, easy rotation
- JWT disabled mode: read-only access plus a generated local proxy script for Claude Desktop
- Never commit tokens; use HTTPS; rotate frequently
- Tool toggles: disable tools you don’t want exposed
- No customer PII is exposed; focus is on public store data and WP content

## Troubleshooting

- “WooCommerce functions not available”: ensure WooCommerce is active
- “Insufficient permissions”: with JWT required, admin capabilities are needed (e.g., `manage_woocommerce`)
- `wc_intelligent_search` returns no products: the tool suggests alternatives; try a less restrictive query
- Admin UI issues: run `npm install && npm run build` in the plugin directory

## Developer Notes

Structure (selection):
```
includes/
  Core/ (McpStdioTransport, McpStreamableTransport, WpMcp, …)
  Admin/ (Settings.php — settings, JWT toggle, tool toggles, proxy generation)
  Tools/ (McpWooProducts, McpWooIntelligentSearch, McpWoo*, …)
  Resources/
src/ (React UI for settings)
```

Build UI:
```
npm install
npm run build
```

Run tests:
```
vendor/bin/phpunit
```

## Changelog

- Full changelog: `changelog.txt` and the “Changelog” page in the docs (synced from GitHub)

## License

This project is licensed under the GPL v2 or later. See the LICENSE file for details.

---

AI Assistant Tips (best practice):
- Always start with `wc_products_search`, then `wc_get_product` for details
- Never hardcode product IDs; use IDs returned from search
- Always include clickable `permalink` links in user-facing answers

## Frequently Asked Questions

<details>
<summary><strong>What is a WooCommerce MCP Server?</strong></summary>
<br>
A server implementation of the Model Context Protocol that exposes WooCommerce and WordPress data to MCP clients (e.g., Claude, VS Code MCP). MCP for WooCommerce is a WordPress plugin that acts as that server.
</details>

<details>
<summary><strong>How do I install the plugin?</strong></summary>
<br>
Upload and activate the plugin, run <code>composer install</code> and <code>npm run build</code> for development installs, then configure settings in WordPress Admin → Settings → MCP for WooCommerce.
</details>

<details>
<summary><strong>How do I connect Claude or VS Code?</strong></summary>
<br>
Use the Streamable endpoint <code>/wp-json/wp/v2/wpmcp/streamable</code> with a JWT header. Examples are in the "Authentication and Clients" section.
</details>

<details>
<summary><strong>Can I add an AI Chatbot to my website?</strong></summary>
<br>
Yes. Use the MCP interface to connect your store data with AI chatbot platforms. Create a JWT token in MCP for WooCommerce settings and configure your chosen AI platform to use the provided endpoints.
</details>

<details>
<summary><strong>Is this read-only? Does it include product links?</strong></summary>
<br>
Yes, all tools are read-only and include <code>permalink</code> fields for products/variations, ideal for customer-facing answers.
</details>

<details>
<summary><strong>Is customer/order data exposed?</strong></summary>
<br>
No. The plugin focuses on public store/catalog data and WordPress content. No PII is exposed.
</details>

<details>
<summary><strong>Is this compatible with Automattic's WordPress MCP?</strong></summary>
<br>
Yes, this community plugin builds upon and extends Automattic's official WordPress MCP implementation. However, this plugin is independently developed and maintained by the community - it is not affiliated with or endorsed by Automattic. It follows the same GPL-2.0-or-later license.
</details>

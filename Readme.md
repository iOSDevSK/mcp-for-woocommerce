# MCP for WooCommerce

[![Latest Release](https://img.shields.io/github/v/release/iOSDevSK/mcp-for-woocommerce)](https://github.com/iOSDevSK/mcp-for-woocommerce/releases) [![License](https://img.shields.io/github/license/iOSDevSK/mcp-for-woocommerce)](https://github.com/iOSDevSK/mcp-for-woocommerce/blob/main/LICENSE) [![GitHub Stars](https://img.shields.io/github/stars/iOSDevSK/mcp-for-woocommerce?style=social)](https://github.com/iOSDevSK/mcp-for-woocommerce/stargazers)

**Connect your WooCommerce store to AI assistants like Claude and VS Code.** This WordPress plugin enables AI clients to access your store's product catalog, categories, reviews, and content through a secure, read-only interface.

> **Community Plugin Notice**: This is a community-developed plugin and is not affiliated with or endorsed by Automattic, the creators of WordPress and WooCommerce. While it builds upon the foundation of the official WordPress MCP implementation, this plugin is independently maintained.

[MCP for WooCommerce](https://mcpforwoocommerce.com) transforms your WordPress site into an AI-accessible data source built on [Automattic's official WordPress MCP](https://github.com/Automattic/wordpress-mcp). It safely exposes public store information—products, categories, tags, reviews, shipping options, and WordPress content—while protecting customer data and private details.

Perfect for building AI-powered shopping assistants or integrating with custom AI applications.

## Key Features

- Read-only: every tool has type `read`; nothing can be created, changed or deleted
- Public storefront data only: published products, categories, tags, attributes, approved reviews, shipping, tax and payment options, published posts and pages
- No user sign-in: the endpoint never acts as a WordPress user and has no administrator access
- Product and variation permalinks: every product and variation includes a `permalink` field
- Two transports: STDIO-style (WordPress REST format) and Streamable HTTP (JSON-RPC 2.0)
- Admin UI: master on/off switch and per-tool toggles
- Smart search: natural-language product search with price, brand, category and attribute filters

## Why Choose MCP for WooCommerce

- WooCommerce MCP Server: turnkey MCP server for WooCommerce + WordPress.
- Nothing to configure: enable it and paste the endpoint URL into your MCP client.
- AI chatbot ready: connect a chat platform and answer shoppers with clickable product links.
- Safe by design: no personal data, no write access, no user impersonation.
- Works with Claude, ChatGPT, Cursor, VS Code, the MCP Inspector and custom MCP clients.

## Architecture and Endpoints

- Streamable HTTP transport (JSON-RPC 2.0) — recommended
  - Endpoint: `/wp-json/wp/v2/wpmcp/streamable`
  - Access: public while MCP is enabled; no credentials

- STDIO-style transport (WordPress REST format)
  - Endpoint: `/wp-json/wp/v2/wpmcp`
  - Access: public while MCP is enabled; no credentials

Tip: If you’re searching for “WooCommerce MCP Server endpoint”, this is it. Use the Streamable HTTP transport for modern, low-latency clients.

## Requirements

- WordPress 6.4+
- PHP 8.0+
- WooCommerce activated
- Node.js (admin UI build), Composer (development)

## Installation

1) WordPress Admin (recommended)
- Install from the plugin directory, or upload the release ZIP via Plugins > Add New > Upload
- Activate the plugin, then switch on **Enable MCP functionality** in Settings > MCP for WooCommerce

2) Development install
```
cd wp-content/plugins/
git clone https://github.com/iOSDevSK/mcp-for-woocommerce.git
cd mcp-for-woocommerce
composer install
npm install && npm run build
```

## AI Shopping Assistant

Deploy AI-powered customer assistance on your site using the MCP data interface.

1) Enable MCP in Settings > MCP for WooCommerce
2) Give your AI platform the endpoint `https://your-site.com/wp-json/wp/v2/wpmcp/streamable`
3) Deploy your chosen chat interface or assistant (for example [Webtalkbot](https://webtalkbot.com))

Result: an AI assistant connected to your catalogue that answers questions with product links and variations.

## Documentation

- Documentation: [mcpforwoocommerce.com](https://mcpforwoocommerce.com)
- Client setup inside WordPress: Settings > MCP for WooCommerce > Documentation

## Admin Settings

- Location: Settings > MCP for WooCommerce
- Enable MCP functionality: master on/off switch; when off, the endpoints are not registered
- Tools: switch individual tools on or off (stored in the `mcpfowo_tool_states` option)

Note: The settings page is a React UI (assets in `build/`).

## Connecting clients

- Claude Code
```
claude mcp add --transport http \
  mcp-for-woocommerce https://your-site.com/wp-json/wp/v2/wpmcp/streamable
```

- Claude Desktop (through `mcp-remote`)
```
{
  "mcpServers": {
    "mcp-for-woocommerce": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "https://your-site.com/wp-json/wp/v2/wpmcp/streamable"]
    }
  }
}
```

- VS Code
```
{
  "servers": {
    "mcp-for-woocommerce": {
      "type": "http",
      "url": "https://your-site.com/wp-json/wp/v2/wpmcp/streamable"
    }
  }
}
```

- MCP Inspector: `npx @modelcontextprotocol/inspector`, choose Streamable HTTP and enter the endpoint URL

- Local STDIO proxy: [`mcp-proxy.php`](mcp-proxy.php) in this repository is a standalone command-line bridge (not part of the plugin package): `php mcp-proxy.php https://your-site.com/wp-json/wp/v2/wpmcp/streamable`

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

- Taxes
  - `wc_get_tax_classes`, `wc_get_tax_rates`

- WordPress content
  - `wordpress_posts_list`, `wordpress_posts_get`
  - `wordpress_pages_list`, `wordpress_pages_get`

Notes:
- Tools are defined under `includes/Tools/*`. Every tool has type `read`; the registry rejects any other type.
- Products, variations, reviews, posts and pages are returned only when an anonymous shop visitor could see them: published, not password-protected, and not hidden from the catalogue.


## Security

- Read-only: the tool registry accepts only `read` tools
- No user sign-in: no tokens, no OAuth, no `wp_set_current_user()`; callers never get a WordPress identity
- Public data only: published, visible products and content; no orders, customers, users, e-mail addresses or settings
- Tool toggles: switch off any tool you don’t want exposed, or switch MCP off entirely

## Troubleshooting

- “WooCommerce functions not available”: ensure WooCommerce is active
- Endpoint returns 404: switch on **Enable MCP functionality**
- Clients that still send an `Authorization` header keep working; the header is ignored
- `wc_intelligent_search` returns no products: the tool suggests alternatives; try a less restrictive query
- Admin UI issues: run `npm install && npm run build` in the plugin directory

## Developer Notes

Structure (selection):
```
includes/
  Core/ (McpStdioTransport, McpStreamableTransport, WpMcp, …)
  Admin/ (Settings.php — MCP switch and tool toggles)
  Tools/ (McpWooProducts, McpWooIntelligentSearch, McpWoo*, …)
  Utils/ (StorefrontVisibility — what an anonymous visitor may see)
  Resources/
src/ (React UI for settings)
```

Build UI:
```
npm install
npm run build
```

End-to-end transport tests: see `tests/e2e/README.md`.

## Changelog

- Full changelog: `changelog.txt` and `readme.txt`

## License

This project is licensed under the GPL v2 or later. See the LICENSE file for details.

## More from the author

[html2wp](https://html2wp.dev) converts a static HTML site (a Lovable, Bolt or v0 export, or hand-written HTML) into a standalone WordPress block theme, with WooCommerce for shops.

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
Install it from the WordPress plugin directory or upload the release ZIP, activate it, then switch on <strong>Enable MCP functionality</strong> in WordPress Admin → Settings → MCP for WooCommerce. For a development install, run <code>composer install</code> and <code>npm run build</code>.
</details>

<details>
<summary><strong>How do I connect Claude or VS Code?</strong></summary>
<br>
Point the client at the Streamable endpoint <code>/wp-json/wp/v2/wpmcp/streamable</code>. No token is needed. Examples are in the "Connecting clients" section.
</details>

<details>
<summary><strong>Can I add an AI Chatbot to my website?</strong></summary>
<br>
Yes. Give your chatbot platform the MCP endpoint URL. It can then search your catalogue and answer with product links.
</details>

<details>
<summary><strong>Is this read-only? Does it include product links?</strong></summary>
<br>
Yes, all tools are read-only and include <code>permalink</code> fields for products/variations, ideal for customer-facing answers.
</details>

<details>
<summary><strong>Is customer/order data exposed?</strong></summary>
<br>
No. The plugin never returns orders, customers, users, e-mail addresses, settings, or unpublished content. It returns only what a shop visitor can already see.
</details>

<details>
<summary><strong>Why is there no authentication?</strong></summary>
<br>
Version 1.3.0 removed JWT and OAuth. The endpoint serves only public storefront information, like the WooCommerce Store API, so a login would protect nothing — and a remote client that signs in as a WordPress user is a security risk this plugin no longer carries. To stop access, switch MCP off in the settings, or switch individual tools off.
</details>

<details>
<summary><strong>Is this compatible with Automattic's WordPress MCP?</strong></summary>
<br>
Yes, this community plugin builds upon and extends Automattic's official WordPress MCP implementation. However, this plugin is independently developed and maintained by the community - it is not affiliated with or endorsed by Automattic. It follows the same GPL-2.0-or-later license.
</details>

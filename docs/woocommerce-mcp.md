# WooCommerce MCP Server — WordPress MCP Plugin (Woo MCP)

Woo MCP is a WordPress MCP plugin that turns your WooCommerce site into a WooCommerce MCP Server. It exposes catalog and content data to MCP clients (Claude, VS Code MCP, custom apps), and pairs with Webtalkbot to deliver a WooCommerce AI Chatbot/Agent on your website.

Keywords: WooCommerce MCP, Woo MCP, WordPress MCP, WooCommerce MCP Server, WooCommerce MCP plugin, WordPress MCP Plugin, WooCommerce AI Chatbot, WooCommerce AI Agent, Webtalkbot.

## Benefits

- Read‑only and safe: no PII exposed; product/variation `permalink` included
- Works with Claude, VS Code MCP, MCP Inspector, and custom MCP clients
- Quick Webtalkbot integration for on‑site WooCommerce AI Chatbot/Agent
- Based on Automattic’s official WordPress MCP (GPL‑2.0‑or‑later)

## Quick Start

1) Install and activate Woo MCP.
2) (Dev) `composer install && npm install && npm run build`.
3) In WordPress Admin → Settings → Woo MCP: enable MCP, configure JWT.
4) Connect a client (Claude/VS Code MCP) to the Streamable endpoint:

```
https://your-site.com/wp-json/wp/v2/wpmcp/streamable
Authorization: Bearer YOUR_JWT
```

STDIO endpoint (for proxy clients):
```
https://your-site.com/wp-json/wp/v2/wpmcp
```

## Webtalkbot: WooCommerce AI Chatbot / Agent

Steps:
1) In Woo MCP, create a JWT token.
2) In Webtalkbot, select WooCommerce as data source and paste the token.
3) Deploy the chat widget to your storefront.

Result: A WooCommerce AI Chatbot/Agent that answers with real product links and variation details.

## Tooling Highlights

- `wc_products_search`, `wc_get_product`, `wc_get_product_variations`, `wc_get_product_variation`
- `wc_get_categories`, `wc_get_tags`, `wc_get_product_attributes`
- Reviews, shipping, payments, taxes, system status
- WordPress posts/pages with permalinks

## Notes

- Requires WordPress 6.4+, PHP 8.0+, WooCommerce
- License: GPL‑2.0‑or‑later (see LICENSE)


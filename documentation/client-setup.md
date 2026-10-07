## Overview

MCP for WooCommerce turns your store into a read-only Model Context Protocol (MCP) server. AI assistants connected to it can answer questions about what a shop visitor can already see on your storefront:

- **Products** – published products, prices, stock status, variations and permalinks
- **Categories, tags and attributes** – the product catalogue structure
- **Reviews** – approved product reviews (reviewer names only, never e-mail addresses)
- **Shipping, taxes and payment methods** – shipping zones and costs, tax rates and the payment methods offered at checkout
- **Posts and pages** – published, non-password-protected WordPress content

## How access works

- The MCP endpoint is **public** while MCP is enabled, like the WooCommerce Store API.
- It is **read-only**: no tool creates, changes or deletes anything on your site.
- It **does not sign in** as a WordPress user. Callers have no WordPress account, role or capability, so nothing they send can perform an administrative action.
- Draft, private, pending, scheduled and password-protected content is never returned, and neither are orders, customers, users, e-mail addresses or settings.
- To stop all access, switch off **Enable MCP functionality** on the Settings tab. You can also switch individual tools off on the Tools tab.

## Endpoint

```
{{your-website.com}}/wp-json/wp/v2/wpmcp/streamable
```

The endpoint speaks Streamable HTTP with JSON-RPC 2.0 messages. Requests must send `Content-Type: application/json` and `Accept: application/json, text/event-stream`.

## Client configurations

### Claude Code

```bash
claude mcp add --transport http mcp-for-woocommerce {{your-website.com}}/wp-json/wp/v2/wpmcp/streamable
```

See the [Claude Code MCP documentation](https://docs.anthropic.com/en/docs/claude-code/mcp) for details.

### Claude Desktop

Claude Desktop connects to remote MCP servers through the `mcp-remote` bridge. Add this to `claude_desktop_config.json`:

```json
{
	"mcpServers": {
		"mcp-for-woocommerce": {
			"command": "npx",
			"args": [ "-y", "mcp-remote", "{{your-website.com}}/wp-json/wp/v2/wpmcp/streamable" ]
		}
	}
}
```

### Cursor

```json
{
	"mcpServers": {
		"mcp-for-woocommerce": {
			"url": "{{your-website.com}}/wp-json/wp/v2/wpmcp/streamable"
		}
	}
}
```

### VS Code

```json
{
	"servers": {
		"mcp-for-woocommerce": {
			"type": "http",
			"url": "{{your-website.com}}/wp-json/wp/v2/wpmcp/streamable"
		}
	}
}
```

### MCP Inspector

```bash
npx @modelcontextprotocol/inspector
```

Choose the **Streamable HTTP** transport and enter `{{your-website.com}}/wp-json/wp/v2/wpmcp/streamable`.

## Available methods

- `initialize` – start a session
- `tools/list` – list the enabled tools
- `tools/call` – run a tool
- `resources/list` and `resources/read` – read the product search guide
- `prompts/list` – list prompts

Example tools: `wc_products_search`, `wc_get_product`, `wc_get_product_variations`, `wc_intelligent_search`, `wc_get_categories`, `wc_get_product_reviews`, `wc_get_shipping_zones`, `wc_get_payment_gateways`, `wordpress_posts_list`, `wordpress_pages_get`.

## Troubleshooting

#### The endpoint returns 404

- Make sure **Enable MCP functionality** is switched on.
- Make sure the WordPress REST API is reachable at `{{your-website.com}}/wp-json/`.

#### Products have no links or broken links

- Set **Settings → Permalinks** to **Post name**.

#### The client cannot connect

- Use HTTPS in production.
- Check that a firewall or security plugin does not block `/wp-json/`.

## Support

- [GitHub repository](https://github.com/iOSDevSK/mcp-for-woocommerce)
- [GitHub issues](https://github.com/iOSDevSK/mcp-for-woocommerce/issues)

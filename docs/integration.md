---
title: LLM Integration Guide — MCP for WooCommerce
summary: How to connect Claude, VS Code MCP, and custom clients to the WooCommerce MCP Server.
tags: [integration, mcp, claude, vscode]
updated: 2025-08-15
---

# LLM Integration Guide — MCP for WooCommerce

Claude (HTTP):
```
claude mcp add --transport http \
  woo-mcp https://your-site.com/wp-json/wp/v2/wpmcp/streamable \
  --header "Authorization: Bearer YOUR_JWT"
```

VS Code MCP Extension (HTTP):
```
{
  "servers": {
    "woo-mcp": {
      "type": "http",
      "url": "https://your-site.com/wp-json/wp/v2/wpmcp/streamable",
      "headers": { "Authorization": "Bearer YOUR_JWT" }
    }
  }
}
```

Claude Desktop (STDIO via proxy):
```
{
  "mcpServers": {
    "woo-mcp": {
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

Security tips:
- Use JWT with short expirations and rotate regularly
- Prefer server‑to‑server HTTP calls for production
- Disable any tools you don’t want exposed

More: [Configure](woocommerce-mcp-server/configure.md) · [Examples](woocommerce-mcp-server/examples.md)


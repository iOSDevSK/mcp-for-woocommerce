---
title: Examples — WooCommerce MCP Server
summary: End‑to‑end examples calling Woo MCP HTTP streamable endpoint with JWT.
tags: [woocommerce, mcp, json-rpc, examples]
updated: 2025-08-15
---

# Examples — HTTP Streamable (JSON‑RPC 2.0)

Endpoint: `https://your-site.com/wp-json/wp/v2/wpmcp/streamable`
Header: `Authorization: Bearer YOUR_JWT`

Find products by search term:
```
POST /wp-json/wp/v2/wpmcp/streamable
{
  "jsonrpc": "2.0",
  "id": "1",
  "method": "tools.call",
  "params": {
    "name": "wc_products_search",
    "arguments": { "query": "running shoes" }
  }
}
```

Get product details (with permalink):
```
{
  "jsonrpc": "2.0",
  "id": "2",
  "method": "tools.call",
  "params": {
    "name": "wc_get_product",
    "arguments": { "id": 123 }
  }
}
```

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"Examples — WooCommerce MCP Server",
  "about":"JSON-RPC examples for Woo MCP HTTP streamable endpoint",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/woo-mcp/woocommerce-mcp-server/examples"}
}
</script>


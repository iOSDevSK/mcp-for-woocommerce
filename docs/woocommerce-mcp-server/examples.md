---
title: Examples — WooCommerce MCP Server
summary: End‑to‑end examples calling MCP for WooCommerce HTTP streamable endpoint with JWT.
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

List product categories:
```
{
  "jsonrpc": "2.0",
  "id": "3",
  "method": "tools.call",
  "params": { "name": "wc_get_categories", "arguments": { } }
}
```

Get variations of a product:
```
{
  "jsonrpc": "2.0",
  "id": "4",
  "method": "tools.call",
  "params": { "name": "wc_get_product_variations", "arguments": { "parent_id": 123 } }
}
```

Filter by attribute (e.g., color=red):
```
{
  "jsonrpc": "2.0",
  "id": "5",
  "method": "tools.call",
  "params": {
    "name": "wc_get_products_filtered",
    "arguments": { "attributes": [{ "name": "color", "values": ["red"] }] }
  }
}
```

List product tags:
```
{
  "jsonrpc": "2.0",
  "id": "6",
  "method": "tools.call",
  "params": { "name": "wc_get_tags", "arguments": {} }
}
```

List global product attributes (definitions):
```
{
  "jsonrpc": "2.0",
  "id": "7",
  "method": "tools.call",
  "params": { "name": "wc_get_product_attributes", "arguments": {} }
}
```

Get product reviews:
```
{
  "jsonrpc": "2.0",
  "id": "8",
  "method": "tools.call",
  "params": {
    "name": "wc_get_product_reviews",
    "arguments": { "product_id": 123, "page": 1, "per_page": 5 }
  }
}
```

Get shipping methods and zones:
```
{
  "jsonrpc": "2.0",
  "id": "9",
  "method": "tools.call",
  "params": { "name": "wc_get_shipping_methods", "arguments": {} }
}
```
```
{
  "jsonrpc": "2.0",
  "id": "10",
  "method": "tools.call",
  "params": { "name": "wc_get_shipping_zones", "arguments": {} }
}
```

Get payment gateways:
```
{
  "jsonrpc": "2.0",
  "id": "11",
  "method": "tools.call",
  "params": { "name": "wc_get_payment_gateways", "arguments": {} }
}
```

Check system status (debug info):
```
{
  "jsonrpc": "2.0",
  "id": "12",
  "method": "tools.call",
  "params": { "name": "wc_get_system_status", "arguments": {} }
}
```

List tax classes and tax rates:
```
{
  "jsonrpc": "2.0",
  "id": "13",
  "method": "tools.call",
  "params": { "name": "wc_get_tax_classes", "arguments": {} }
}
```
```
{
  "jsonrpc": "2.0",
  "id": "14",
  "method": "tools.call",
  "params": { "name": "wc_get_tax_rates", "arguments": {} }
}
```

List WordPress posts and get a post:
```
{
  "jsonrpc": "2.0",
  "id": "15",
  "method": "tools.call",
  "params": { "name": "wordpress_posts_list", "arguments": { "page": 1, "per_page": 5 } }
}
```
```
{
  "jsonrpc": "2.0",
  "id": "16",
  "method": "tools.call",
  "params": { "name": "wordpress_posts_get", "arguments": { "id": 1234 } }
}
```

List WordPress pages and get a page:
```
{
  "jsonrpc": "2.0",
  "id": "17",
  "method": "tools.call",
  "params": { "name": "wordpress_pages_list", "arguments": { "page": 1, "per_page": 5 } }
}
```
```
{
  "jsonrpc": "2.0",
  "id": "18",
  "method": "tools.call",
  "params": { "name": "wordpress_pages_get", "arguments": { "id": 55 } }
}
```

List attribute terms (e.g., Color terms):
```
{
  "jsonrpc": "2.0",
  "id": "19",
  "method": "tools.call",
  "params": { "name": "wc_get_attribute_terms", "arguments": { "attribute_id": 12 } }
}
```

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"Examples — WooCommerce MCP Server",
  "about":"JSON-RPC examples for MCP for WooCommerce HTTP streamable endpoint",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/mcp-for-woocommerce/woocommerce-mcp-server/examples"}
}
</script>

---
title: Troubleshooting — WooCommerce MCP Server
summary: Common errors and fixes for Woo MCP HTTP/STDIO endpoints and JWT.
tags: [woocommerce, mcp, troubleshooting, jwt]
updated: 2025-08-15
---

# Troubleshooting

Problem: 401 Unauthorized on HTTP streamable endpoint
- Cause: Missing/invalid JWT
- Fix: Include `Authorization: Bearer YOUR_JWT`; verify token validity/expiration

Problem: WooCommerce functions not available
- Cause: WooCommerce plugin inactive
- Fix: Activate WooCommerce; ensure it loads before tool calls

Problem: No products returned
- Cause: Too restrictive query
- Fix: Start with `wc_products_search` and broaden terms; then fetch details via `wc_get_product`

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"Troubleshooting — WooCommerce MCP Server",
  "about":"Common errors and fixes for Woo MCP",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/woo-mcp/woocommerce-mcp-server/troubleshooting"}
}
</script>


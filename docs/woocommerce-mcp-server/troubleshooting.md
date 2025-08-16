---
title: Troubleshooting — WooCommerce MCP Server
summary: Common errors and fixes for MCP for WooCommerce HTTP/STDIO endpoints and JWT.
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

Problem: 403 Forbidden
- Cause: Token missing required capability or server rules block request
- Fix: Recreate JWT with proper scope; check security/firewall rules

Problem: 404 on endpoints
- Cause: Wrong base URL or permalink structure
- Fix: Confirm `/wp-json/wp/v2/wpmcp` and `/wp-json/wp/v2/wpmcp/streamable`; refresh permalinks

Problem: CORS errors from custom frontends
- Cause: Browser preflight blocked
- Fix: Use server‑side integrations or configure reverse proxy/CORS; prefer server‑to‑server calls

Problem: Missing `permalink` in results
- Cause: Using non‑MCP for WooCommerce endpoints or incomplete setup
- Fix: Call MCP for WooCommerce tools (`wc_*`) which include permalinks by design

Problem: Admin UI not reflecting changes
- Cause: Build assets outdated
- Fix: `npm install && npm run build` in the plugin directory

Problem: PHPUnit cannot bootstrap
- Cause: WordPress tests library not installed
- Fix: Run `bin/install-wp-tests.sh` and set `WP_TESTS_DIR`; then `vendor/bin/phpunit`

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"Troubleshooting — WooCommerce MCP Server",
  "about":"Common errors and fixes for MCP for WooCommerce",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/woo-mcp/woocommerce-mcp-server/troubleshooting"}
}
</script>

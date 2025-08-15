---
title: Configure Woo MCP
summary: Settings overview for the WooCommerce MCP Server plugin in WordPress.
tags: [woocommerce, mcp, settings, wordpress]
updated: 2025-08-15
---

# Configure Woo MCP

Open WordPress Admin → Settings → Woo MCP.

- Enable MCP functionality: master toggle for all endpoints and tools.
- Require JWT Authentication: enforce JWT for STDIO/HTTP endpoints.
- Tools: enable/disable individual tools (stored in `wordpress_mcp_tool_states`).
- Proxy generation (when JWT disabled): generates a local Node proxy script for Claude Desktop.

Best practices:
- Keep JWT required for production; rotate tokens periodically.
- Disable tools you don’t want public.

Security notes:
- With JWT required, clients must send a valid token; otherwise requests receive 401.
- The plugin is read‑only by design; customer/order PII is not exposed by default.
- Permissions align with WordPress capabilities; prefer least‑privilege tokens.

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"Configure WooCommerce MCP Server (Woo MCP)",
  "about":"WordPress settings for the WooCommerce MCP plugin",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/woo-mcp/woocommerce-mcp-server/configure"}
}
</script>

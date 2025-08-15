---
title: Woo MCP — WooCommerce MCP Server
summary: WordPress MCP plugin that exposes WooCommerce and WordPress data to Model Context Protocol (MCP) clients. Read‑only tools with permalinks; JWT; STDIO and HTTP streamable endpoints.
tags: [woocommerce, mcp, wordpress, json-rpc, jwt]
updated: 2025-08-15
---

# Woo MCP — WooCommerce MCP Server for WordPress

Woo MCP is a WordPress MCP plugin that acts as a WooCommerce MCP Server. It provides read‑only tools for products, variations, categories, tags, attributes, reviews, shipping, payments, taxes, system status, and WordPress posts/pages — all with permalinks.

Key points:
- Transports: STDIO and HTTP (JSON‑RPC 2.0 streamable)
- Auth: JWT (with optional local proxy for STDIO)
- Safety: read‑only design; no customer/order PII exposed
- Chatbot/Agent: pair with Webtalkbot to deploy an on‑site WooCommerce AI Chatbot/Agent

Quick start (TL;DR):
1) Install and activate the plugin
2) In WP Admin → Settings → Woo MCP: enable MCP + generate JWT
3) Connect clients to `/wp-json/wp/v2/wpmcp/streamable` with `Authorization: Bearer <token>`

Start here:
- What is it? → [What is WooCommerce MCP Server?](woocommerce-mcp-server/what-is.md)
- Install → [Install](woocommerce-mcp-server/install.md)
- Configure → [Configure](woocommerce-mcp-server/configure.md)
- Examples → [Examples](woocommerce-mcp-server/examples.md)
- Troubleshooting → [Troubleshooting](woocommerce-mcp-server/troubleshooting.md)
- FAQ → [FAQ](woocommerce-mcp-server/faq.md)

Benefits:
- Easy integration with Claude, VS Code MCP, and custom MCP clients
- Consistent, permalink‑rich responses for product/variation details
- Secure by default with JWT; production‑ready setup in minutes

Project links:
- GitHub repository: https://github.com/iOSDevSK/woo-mcp
- Documentation site: https://iosdevsk.github.io/woo-mcp/

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Woo MCP",
  "description": "WordPress MCP plugin that exposes WooCommerce and WordPress data to Model Context Protocol (MCP) clients via STDIO and HTTP streamable endpoints with JWT authentication.",
  "applicationCategory": "PluginApplication",
  "operatingSystem": "WordPress",
  "softwareVersion": "0.2.9",
  "dateModified": "2025-08-15",
  "url": "https://github.com/iOSDevSK/woo-mcp",
  "downloadUrl": "https://github.com/iOSDevSK/woo-mcp/releases",
  "author": {
    "@type": "Organization",
    "name": "iOSDevSK"
  },
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD"
  },
  "mainEntityOfPage": {
    "@type": "WebPage", 
    "@id": "https://iosdevsk.github.io/woo-mcp/"
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "Woo MCP — WooCommerce MCP Server for WordPress",
  "about": "WooCommerce MCP server implementing the Model Context Protocol (MCP)",
  "description": "WordPress MCP plugin that exposes WooCommerce and WordPress data to MCP clients via STDIO and HTTP streamable endpoints with JWT.",
  "dateModified": "2025-08-15",
  "mainEntityOfPage": {"@type": "WebPage", "@id": "https://iosdevsk.github.io/woo-mcp/"}
}
</script>

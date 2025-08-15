---
title: What is a WooCommerce MCP Server?
summary: Short definition of Woo MCP as a WordPress MCP plugin that exposes WooCommerce data to MCP clients via STDIO/HTTP with JWT.
tags: [woocommerce, mcp, server, wordpress, json-rpc]
updated: 2025-08-15
---

# What is a WooCommerce MCP Server?

TL;DR: A WooCommerce MCP Server is a WordPress MCP plugin that implements the Model Context Protocol (MCP) for WooCommerce and WordPress. It exposes standardized read‑only tools (e.g., product search with permalinks) to MCP clients via STDIO and an HTTP JSON‑RPC 2.0 streamable endpoint secured by JWT.

Why it matters:
- Standard tools that LLM/agents can discover and call
- WooCommerce‑focused: products, variations, categories, attributes, reviews
- Safe by default: read‑only data; includes permalinks for clickable answers

Related endpoints:
- STDIO: `/wp-json/wp/v2/wpmcp`
- HTTP streamable: `/wp-json/wp/v2/wpmcp/streamable`

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"What is a WooCommerce MCP Server?",
  "about":"Model Context Protocol for WooCommerce on WordPress",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/woo-mcp/woocommerce-mcp-server/what-is"}
}
</script>


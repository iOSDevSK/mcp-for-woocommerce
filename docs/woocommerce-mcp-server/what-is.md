---
title: What is a WooCommerce MCP Server?
summary: Short definition of MCP for WooCommerce as a WordPress MCP plugin that exposes WooCommerce data to MCP clients via STDIO/HTTP with JWT.
tags: [woocommerce, mcp, server, wordpress, json-rpc]
updated: 2025-08-15
---

# What is a WooCommerce MCP Server?

TL;DR: A WooCommerce MCP Server is a WordPress MCP plugin that implements the Model Context Protocol (MCP) for WooCommerce and WordPress. It exposes standardized read‑only tools (e.g., product search with permalinks) to MCP clients via STDIO and an HTTP JSON‑RPC 2.0 streamable endpoint secured by JWT.

Key capabilities:
- WooCommerce‑focused tools: products, variations, categories, tags, attributes, reviews, shipping, payments, taxes.
- WordPress content tools: posts and pages with permalinks.
- Built‑in permissions: read‑only by design; no customer/order PII.
- Dual transports: STDIO for broad compatibility; HTTP streamable for low‑latency JSON‑RPC 2.0.

Typical use cases:
- Power an on‑site WooCommerce AI Chatbot/Agent (via Webtalkbot) with real product links.
- Let developer tools (Claude, VS Code MCP) query products, categories, attributes.
- Build custom MCP clients that fetch catalog content safely.

Related endpoints:
- STDIO: `/wp-json/wp/v2/wpmcp`
- HTTP streamable: `/wp-json/wp/v2/wpmcp/streamable`

Synonyms people search for:
- WooCommerce MCP, MCP for WooCommerce, WordPress MCP, WooCommerce MCP Server, WooCommerce MCP plugin, WordPress MCP Plugin, WooCommerce AI Chatbot/Agent, Webtalkbot.

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"What is a WooCommerce MCP Server?",
  "about":"Model Context Protocol for WooCommerce on WordPress",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/mcp-for-woocommerce/woocommerce-mcp-server/what-is"}
}
</script>

---
title: FAQ — WooCommerce MCP Server (MCP for WooCommerce)
summary: Short Q&A about WordPress MCP for WooCommerce, endpoints, JWT, and Webtalkbot.
tags: [woocommerce, mcp, faq, jwt, webtalkbot]
updated: 2025-08-15
---

# FAQ — WooCommerce MCP Server

Q: What is MCP for WooCommerce?
A: A WordPress MCP plugin that implements a WooCommerce MCP Server exposing read‑only tools to MCP clients via STDIO and HTTP streamable endpoints.

Q: Does it support HTTP (streamable)?
A: Yes — `/wp-json/wp/v2/wpmcp/streamable` using JSON‑RPC 2.0 with JWT.

Q: How do I generate JWT tokens?
A: In WordPress Admin → Settings → MCP for WooCommerce → Authentication Tokens.

Q: Can I deploy a WooCommerce AI Chatbot/Agent?
A: Yes. Pair MCP for WooCommerce with Webtalkbot to add a storefront chatbot/agent in minutes.

Q: Are product links included?
A: Yes. Tools return `permalink` for products and variations.

Q: Is MCP for WooCommerce read‑only?
A: Yes, by design all built‑in tools are read‑only and safe for public data exposure.

Q: Which MCP clients are supported?
A: Claude (Code/Desktop), VS Code MCP Extension, MCP Inspector, and custom clients supporting STDIO or HTTP JSON‑RPC 2.0.

Q: What’s the difference between STDIO vs HTTP streamable?
A: STDIO maximizes compatibility; HTTP streamable offers low latency and simpler network setups with JWT.

Q: Can I disable specific tools?
A: Yes. Use the Tools toggles in Settings → MCP for WooCommerce to disable any tool you don’t want exposed.

Q: Do I need WooCommerce installed?
A: Yes, for WooCommerce tools to work. The plugin also includes WordPress content tools (posts/pages).

Q: How do I test endpoints quickly?
A: Use MCP Inspector or cURL with JSON‑RPC requests to `/wpmcp/streamable` and include the JWT header.

Q: Is this compatible with Automattic’s WordPress MCP?
A: Yes. It’s based on the official implementation and licensed GPL‑2.0‑or‑later.

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"FAQPage",
  "mainEntity":[
    {"@type":"Question","name":"What is MCP for WooCommerce?","acceptedAnswer":{"@type":"Answer","text":"A WordPress MCP plugin that implements a WooCommerce MCP Server exposing read-only tools via STDIO and HTTP streamable endpoints."}},
    {"@type":"Question","name":"Does it support HTTP (streamable)?","acceptedAnswer":{"@type":"Answer","text":"Yes — /wp-json/wp/v2/wpmcp/streamable using JSON-RPC 2.0 with JWT."}},
    {"@type":"Question","name":"How to generate JWT tokens?","acceptedAnswer":{"@type":"Answer","text":"In WordPress Admin → Settings → MCP for WooCommerce → Authentication Tokens."}},
    {"@type":"Question","name":"Can I deploy a WooCommerce AI Chatbot/Agent?","acceptedAnswer":{"@type":"Answer","text":"Yes. Pair MCP for WooCommerce with Webtalkbot to add a storefront chatbot/agent in minutes."}},
    {"@type":"Question","name":"Are product links included?","acceptedAnswer":{"@type":"Answer","text":"Yes. Tools return permalink for products and variations."}}
  ]
}
</script>

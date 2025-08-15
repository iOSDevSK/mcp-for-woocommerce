---
title: FAQ — WooCommerce MCP Server (Woo MCP)
summary: Short Q&A about WordPress MCP for WooCommerce, endpoints, JWT, and Webtalkbot.
tags: [woocommerce, mcp, faq, jwt, webtalkbot]
updated: 2025-08-15
---

# FAQ — WooCommerce MCP Server

Q: What is Woo MCP?
A: A WordPress MCP plugin that implements a WooCommerce MCP Server exposing read‑only tools to MCP clients via STDIO and HTTP streamable endpoints.

Q: Does it support HTTP (streamable)?
A: Yes — `/wp-json/wp/v2/wpmcp/streamable` using JSON‑RPC 2.0 with JWT.

Q: How do I generate JWT tokens?
A: In WordPress Admin → Settings → Woo MCP → Authentication Tokens.

Q: Can I deploy a WooCommerce AI Chatbot/Agent?
A: Yes. Pair Woo MCP with Webtalkbot to add a storefront chatbot/agent in minutes.

Q: Are product links included?
A: Yes. Tools return `permalink` for products and variations.

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"FAQPage",
  "mainEntity":[
    {"@type":"Question","name":"What is Woo MCP?","acceptedAnswer":{"@type":"Answer","text":"A WordPress MCP plugin that implements a WooCommerce MCP Server exposing read-only tools via STDIO and HTTP streamable endpoints."}},
    {"@type":"Question","name":"Does it support HTTP (streamable)?","acceptedAnswer":{"@type":"Answer","text":"Yes — /wp-json/wp/v2/wpmcp/streamable using JSON-RPC 2.0 with JWT."}},
    {"@type":"Question","name":"How to generate JWT tokens?","acceptedAnswer":{"@type":"Answer","text":"In WordPress Admin → Settings → Woo MCP → Authentication Tokens."}},
    {"@type":"Question","name":"Can I deploy a WooCommerce AI Chatbot/Agent?","acceptedAnswer":{"@type":"Answer","text":"Yes. Pair Woo MCP with Webtalkbot to add a storefront chatbot/agent in minutes."}},
    {"@type":"Question","name":"Are product links included?","acceptedAnswer":{"@type":"Answer","text":"Yes. Tools return permalink for products and variations."}}
  ]
}
</script>


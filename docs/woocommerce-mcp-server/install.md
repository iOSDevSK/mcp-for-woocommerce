---
title: Install MCP for WooCommerce (WooCommerce MCP Server)
summary: Quick setup for the WordPress MCP plugin providing WooCommerce MCP Server endpoints with JWT.
tags: [woocommerce, mcp, install, wordpress, jwt]
updated: 2025-08-15
---

# How to install MCP for WooCommerce (WooCommerce MCP Server)

1) WordPress Admin (quick install)
- Plugins → Add New → Upload → select ZIP of the plugin
- Activate "MCP for WooCommerce"

2) Development install (from GitHub)
```
cd wp-content/plugins/
git clone https://github.com/iOSDevSK/mcp-for-woocommerce.git mcp-for-woocommerce
cd mcp-for-woocommerce
composer install
npm install && npm run build
```

3) Prerequisites
- WordPress 6.4+, PHP 8.0+
- WooCommerce active and working

4) Configure in Admin → Settings → MCP for WooCommerce
- Enable MCP functionality
- Require JWT Authentication (recommended)
- Create a JWT token in “Authentication Tokens”

5) Endpoints
- STDIO: `/wp-json/wp/v2/wpmcp`
- HTTP (streamable): `/wp-json/wp/v2/wpmcp/streamable`

6) Connect clients
- HTTP header: `Authorization: Bearer YOUR_JWT`

Claude Code (HTTP example):
```
claude mcp add --transport http \
  woo-mcp https://your-site.com/wp-json/wp/v2/wpmcp/streamable \
  --header "Authorization: Bearer YOUR_JWT"
```

VS Code MCP Extension (HTTP example):
```
{
  "servers": {
    "woo-mcp": {
      "type": "http",
      "url": "https://your-site.com/wp-json/wp/v2/wpmcp/streamable",
      "headers": { "Authorization": "Bearer YOUR_JWT" }
    }
  }
}
```

Claude Desktop (STDIO via proxy):
```
{
  "mcpServers": {
    "woo-mcp": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://your-site.com",
        "JWT_TOKEN": "YOUR_JWT"
      }
    }
  }
}
```

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"How to install WooCommerce MCP Server",
  "about":"Install and configure MCP for WooCommerce with HTTP streamable and STDIO endpoints",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/woo-mcp/woocommerce-mcp-server/install"}
}
</script>

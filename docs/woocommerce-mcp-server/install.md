---
title: Install Woo MCP (WooCommerce MCP Server)
summary: Quick setup for the WordPress MCP plugin providing WooCommerce MCP Server endpoints with JWT.
tags: [woocommerce, mcp, install, wordpress, jwt]
updated: 2025-08-15
---

# How to install Woo MCP (WooCommerce MCP Server)

1) WordPress Admin
- Upload/activate the plugin in Plugins → Add New → Upload

2) Development install
```
cd wp-content/plugins/
git clone https://github.com/iOSDevSK/woo-mcp.git woo-mcp
cd woo-mcp
composer install
npm install && npm run build
```

3) Configure in Admin → Settings → Woo MCP
- Enable MCP functionality
- (Recommended) Require JWT authentication
- Generate a JWT token

4) Connect clients
- HTTP (streamable): `https://your-site.com/wp-json/wp/v2/wpmcp/streamable`
- Header: `Authorization: Bearer YOUR_JWT`

Claude Code (example):
```
claude mcp add --transport http \
  woo-mcp https://your-site.com/wp-json/wp/v2/wpmcp/streamable \
  --header "Authorization: Bearer YOUR_JWT"
```

VS Code MCP Extension (example):
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

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"How to install WooCommerce MCP Server",
  "about":"Install and configure Woo MCP with HTTP streamable and STDIO endpoints",
  "dateModified":"2025-08-15",
  "mainEntityOfPage":{"@type":"WebPage","@id":"https://iosdevsk.github.io/woo-mcp/woocommerce-mcp-server/install"}
}
</script>


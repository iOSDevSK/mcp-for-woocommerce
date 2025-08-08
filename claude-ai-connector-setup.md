# Claude.ai Online Custom Connector Setup

## Verified Working Configuration

Server endpoint is working correctly. Follow these exact steps:

### 1. Claude.ai Custom Connector Settings

**Connector URL:**
```
https://woo.webtalkbot.com/wp-json/wp/v2/wpmcp/streamable
```

**Required Headers:**
```
Content-Type: application/json
Accept: application/json, text/event-stream
```

**Method:** POST

### 2. Test Payload (for validation)

Initialize request:
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {
    "protocolVersion": "2024-11-05",
    "capabilities": {
      "tools": {}
    },
    "clientInfo": {
      "name": "claude-web",
      "version": "1.0.0"
    }
  }
}
```

### 3. Expected Response

Should return server info and capabilities:
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2025-03-26",
    "serverInfo": {
      "name": "WordPress MCP Server",
      "version": "1.1.2",
      "siteInfo": {
        "name": "WooCommerce MCP Demo",
        "url": "https://woo.webtalkbot.com"
      }
    },
    "capabilities": {
      "tools": {
        "list": true,
        "call": true
      }
    }
  }
}
```

### 4. Troubleshooting

- ✅ CORS is configured correctly
- ✅ JWT authentication is disabled  
- ✅ Server responds to initialize requests
- ✅ Tools/list endpoint returns 25+ WooCommerce tools

**If still disabled in Claude.ai:**
1. Clear browser cache
2. Try different browser/incognito mode
3. Wait 5-10 minutes for Claude.ai to refresh connector status
4. Re-add the connector with exact URL above
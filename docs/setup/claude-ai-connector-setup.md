# Claude.ai Online Custom Connector Setup

Replace `https://your-site.com` below with your own WooCommerce site URL.

## 1. Claude.ai Custom Connector Settings

**Connector URL:**
```
https://your-site.com/wp-json/wp/v2/wpmcp/streamable
```

**Required Headers:**
```
Content-Type: application/json
Accept: application/json, text/event-stream
```

The `Accept` header must list **both** `application/json` and `text/event-stream` —
the endpoint rejects a request that carries only one of them.

If JWT is required (the default), also send:
```
Authorization: Bearer <your-token>
```

Generate a token in **Settings → MCP for WooCommerce**, or via:
```bash
curl -X POST https://your-site.com/wp-json/mcpfowo/v1/auth/token \
  -H 'Content-Type: application/json' \
  -d '{"username":"<user>","password":"<application-password>"}'
```

**Method:** POST

## 2. Test Payload (for validation)

Initialize request:
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {
    "protocolVersion": "2025-06-18",
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

## 3. Expected Response

A single, complete `application/json` body — server info and capabilities:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2025-06-18",
    "serverInfo": {
      "name": "WordPress MCP Server",
      "version": "1.2.3",
      "siteInfo": {
        "name": "Your Store",
        "url": "https://your-site.com"
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

The response also carries an `Mcp-Session-Id` header. There is no chunk framing in
the body — if you see a hex number and a blank line before the `{`, you are running
a version older than 1.2.3 (see GitHub issue #5).

## 4. Troubleshooting

Verify the endpoint from a shell before blaming the client:

```bash
curl -sS -D- -X POST https://your-site.com/wp-json/wp/v2/wpmcp/streamable \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -H 'Authorization: Bearer <your-token>' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}'
```

Checklist:

- **HTTP 403 `mcp_disabled`** — MCP is turned off in the plugin settings.
- **HTTP 403 `rest_forbidden`** — token missing, expired, or revoked.
- **HTTP 400 `-32006`** — the `Accept` header is missing `application/json` or `text/event-stream`.
- **Empty `tools` list** — WooCommerce is not active, or tools are disabled in settings.
- **Body does not parse as JSON** — upgrade to 1.2.3 or later.

**If the connector still shows as disabled in Claude.ai:**
1. Clear browser cache
2. Try a different browser / incognito window
3. Wait 5-10 minutes for Claude.ai to refresh connector status
4. Re-add the connector with the exact URL above

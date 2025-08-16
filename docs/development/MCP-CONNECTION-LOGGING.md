# MCP Connection Logging Enhancement

## Overview

Enhanced comprehensive logging system for tracking MCP server connection attempts, both successful and failed, with detailed request/response information specifically for debugging Claude.ai web app connections to mcp-for-woocommerce at https://woo.webtalkbot.com.

## Features Added

### 1. Connection Source Detection
- **Claude.ai Web App**: Detects based on User-Agent, Anthropic-Beta headers, referrer
- **Claude Desktop**: Identifies Claude Desktop application connections  
- **MCP Proxy**: Detects connections from PHP/Node.js MCP proxies
- **Generic Clients**: Identifies curl, Postman, and other HTTP clients
- **Unknown Sources**: Captures unidentified connection attempts

### 2. Enhanced Request Logging
- Connection source identification
- Request method and endpoint details
- User agent and header analysis
- Request body size and content
- Remote IP address tracking
- Authentication status (JWT present/missing)
- Timestamp with millisecond precision

### 3. Enhanced Response Logging  
- Success/failure detection
- Error code and message extraction
- Response type analysis
- Processing duration measurement
- Connection result categorization

### 4. Multiple Log Files
- **mcp-claude-debug.log**: Detailed Claude.ai specific debugging
- **mcp-connections.log**: General connection attempts and results
- **mcp-claude-connections.log**: Claude.ai web app specific connections
- **mcp-connection-failures.log**: Failed connection attempts only
- **WordPress debug.log**: Standard WordPress error logging

## Log File Locations

All log files are created in the WordPress `wp-content/` directory:

```
wp-content/
├── mcp-claude-debug.log          # Detailed Claude.ai debugging
├── mcp-connections.log           # All connection attempts
├── mcp-claude-connections.log    # Claude.ai specific connections  
├── mcp-connection-failures.log   # Failed connections only
└── debug.log                     # Standard WordPress debug log
```

## Log Entry Formats

### Connection Attempt Log
```json
{
  "event": "connection_attempt",
  "timestamp": "2025-08-09 17:30:15",
  "source": "claude.ai-webapp",
  "method": "POST",
  "remote_addr": "192.168.1.100",
  "user_agent": "Mozilla/5.0 claude.ai",
  "endpoint": "https://woo.webtalkbot.com/wp-json/wp/v2/wpmcp/streamable",
  "has_auth": true,
  "anthropic_beta": "mcp-2025-06-18",
  "request_size": 156
}
```

### Connection Result Log
```json
{
  "event": "connection_result", 
  "timestamp": "2025-08-09 17:30:15",
  "success": false,
  "error_code": -32601,
  "error_message": "Method not found",
  "response_type": "array"
}
```

### PHP Proxy Enhanced Logging
```json
{
  "event": "proxy_connection_failure",
  "timestamp": "2025-08-09 17:30:15", 
  "method": "initialize",
  "endpoint": "https://woo.webtalkbot.com/wp-json/wp/v2/wpmcp/streamable",
  "error": "HTTP request failed",
  "duration_ms": 585.49,
  "pid": 12345
}
```

## Files Modified

### Core Transport Layer
- **includes/Core/McpStreamableTransport.php**
  - Added `detect_connection_source()` method
  - Added `log_connection_attempt()` method  
  - Added `log_connection_result()` method
  - Enhanced `log_claude_request()` and `log_claude_response()`

### PHP Proxy Files
- **mcp-proxy.php**
  - Added detailed connection attempt/success/failure logging
  - Added timing measurement for requests
  - Enhanced error handling with detailed context

- **mcp-server.php** 
  - Added comprehensive cURL request logging
  - Added connection timing and performance metrics
  - Enhanced error reporting with HTTP status details

## Testing

Created `test-connection-logging.php` script that simulates various connection scenarios:

1. Claude.ai web app connection (with Anthropic-Beta header)
2. Claude Desktop connection
3. MCP proxy connection  
4. Unknown client connection
5. Invalid request (to test failure logging)

## Usage for Debugging Failed Claude.ai Connections

### 1. Check Connection Attempts
```bash
tail -f wp-content/mcp-claude-connections.log
```

### 2. Monitor Failed Connections
```bash
tail -f wp-content/mcp-connection-failures.log
```

### 3. Detailed Request/Response Analysis
```bash
tail -f wp-content/mcp-claude-debug.log
```

### 4. Real-time All Connections
```bash
tail -f wp-content/mcp-connections.log
```

## Key Benefits

1. **Precise Problem Identification**: Quickly identify if Claude.ai web app connections are reaching the server
2. **Error Classification**: Distinguish between network issues, authentication problems, and application errors
3. **Performance Monitoring**: Track connection timing and identify slow requests
4. **Source Tracking**: Know exactly which client type is attempting connections
5. **Comprehensive History**: Full audit trail of all connection attempts with timestamps

## Configuration

The logging system is automatically enabled and requires no additional configuration. It respects the WordPress `WP_DEBUG` setting for verbose logging to the standard debug.log file.

Log files are automatically created when the first connection attempt occurs and will grow incrementally. Consider implementing log rotation for production environments with high traffic.

## Time-specific Debugging

For the reported failed connection at 17:30, you can search logs with:
```bash
grep "17:30" wp-content/mcp-*-debug.log
grep "17:30" wp-content/mcp-connections.log  
```

This will show all connection activity around that specific time window to help identify what went wrong with the Claude.ai web app connection attempt.
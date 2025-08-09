#!/usr/bin/env node
/**
 * MCP Proxy Server for WordPress MCP Plugin
 * Automatically generated - connects Claude.ai Desktop to WordPress MCP endpoints
 *
 * Generated on: 2025-01-08T23:30:00+01:00
 * WordPress Site: https://woo.webtalkbot.com
 * MCP Endpoint: https://woo.webtalkbot.com/wp-json/wp/v2/wpmcp/streamable
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { ListToolsRequestSchema, CallToolRequestSchema } from '@modelcontextprotocol/sdk/types.js';

const WORDPRESS_MCP_URL = 'https://woo.webtalkbot.com/wp-json/wp/v2/wpmcp/streamable';

class McpProxy {
  constructor() {
    this.server = new Server(
      {
        name: 'woocommerce-mcp-proxy',
        version: '1.0.0',
      },
      {
        capabilities: {
          tools: {},
        },
      }
    );

    this.setupHandlers();
    this.setupErrorHandling();
  }

  setupErrorHandling() {
    this.server.onerror = (error) => {
      console.error('[MCP Proxy] Server error:', error);
    };

    process.on('SIGINT', async () => {
      await this.server.close();
      process.exit(0);
    });
  }

  setupHandlers() {
    // Proxy tools/list requests
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      try {
        const response = await this.forwardRequest('tools/list', {});
        return response.result || { tools: [] };
      } catch (error) {
        console.error('Error forwarding tools/list:', error);
        return { tools: [] };
      }
    });

    // Proxy tools/call requests
    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      try {
        const response = await this.forwardRequest('tools/call', request.params);
        return response.result || { content: [{ type: 'text', text: 'Error executing tool' }] };
      } catch (error) {
        console.error('Error forwarding tools/call:', error);
        return { content: [{ type: 'text', text: `Error: ${error.message}` }] };
      }
    });
  }

  async forwardRequest(method, params) {
    const requestBody = {
      jsonrpc: '2.0',
      id: Math.random().toString(36).substring(7),
      method: method,
      params: params || {}
    };

    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json, text/event-stream'
    };

    const response = await fetch(WORDPRESS_MCP_URL, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(requestBody)
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    
    if (data.error) {
      throw new Error(data.error.message || 'WordPress MCP Error');
    }

    return data;
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error('[MCP Proxy] Woo-MCP proxy server running...');
  }
}

const proxy = new McpProxy();
proxy.run().catch(console.error);
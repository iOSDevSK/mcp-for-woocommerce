#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';

const WORDPRESS_URL = process.env.WORDPRESS_URL || 'https://woo.webtalkbot.com';
const ENDPOINT_URL = `${WORDPRESS_URL}/wp-json/wp/v2/wpmcp/streamable`;

console.error(`[MCP WordPress Server] Starting MCP WordPress Server`);
console.error(`[MCP WordPress Server] Connecting to: ${ENDPOINT_URL}`);

// Create MCP server
const server = new Server(
  {
    name: 'wordpress-mcp-proxy',
    version: '1.0.0',
  },
  {
    capabilities: {
      tools: {},
      resources: {},
      prompts: {},
    }
  }
);

// Forward all requests to WordPress
async function forwardToWordPress(method, params) {
  console.error(`[MCP WordPress Server] Forwarding ${method} to WordPress`);
  
  try {
    const response = await fetch(ENDPOINT_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json, text/event-stream'
      },
      body: JSON.stringify({
        jsonrpc: '2.0',
        id: 1,
        method: method,
        params: params || {}
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    console.error(`[MCP WordPress Server] WordPress responded for ${method}`);
    
    if (data.error) {
      throw new Error(`WordPress error: ${data.error.message}`);
    }
    
    return data.result;
  } catch (error) {
    console.error(`[MCP WordPress Server] Error forwarding to WordPress:`, error.message);
    throw error;
  }
}

// Handle tools/list
server.setRequestHandler('tools/list', async () => {
  const result = await forwardToWordPress('tools/list');
  return result;
});

// Handle tools/call
server.setRequestHandler('tools/call', async (request) => {
  const result = await forwardToWordPress('tools/call', request.params);
  return result;
});

// Handle resources/list
server.setRequestHandler('resources/list', async () => {
  const result = await forwardToWordPress('resources/list');
  return result;
});

// Handle prompts/list
server.setRequestHandler('prompts/list', async () => {
  const result = await forwardToWordPress('prompts/list');
  return result;
});

// Start the server
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('[MCP WordPress Server] Server connected and ready');
}

main().catch((error) => {
  console.error('[MCP WordPress Server] Error:', error);
  process.exit(1);
});
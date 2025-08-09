<?php
/**
 * MCP Proxy Generator
 * 
 * Generates JavaScript MCP proxy server when JWT authentication is disabled
 *
 * @package WordPressMcp
 */
namespace Automattic\WordpressMcp\Core;

use WP_Error;

class McpProxyGenerator {

    /**
     * Check if proxy file should be generated
     *
     * @return bool
     */
    public static function should_generate_proxy(): bool {
        $jwt_required = get_option('wordpress_mcp_jwt_required', true);
        
        // Generate proxy when JWT is disabled (false, "0", 0, or empty string)
        return !$jwt_required || $jwt_required === '0' || $jwt_required === 0;
    }

    /**
     * Generate MCP proxy server file
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function generate_proxy_file() {
        $proxy_path = self::get_proxy_file_path();
        $proxy_content = self::generate_proxy_content();

        $result = file_put_contents($proxy_path, $proxy_content);

        if ($result === false) {
            return new WP_Error(
                'proxy_generation_failed',
                'Failed to generate MCP proxy file',
                array('path' => $proxy_path)
            );
        }

        // Make file executable
        chmod($proxy_path, 0755);

        return true;
    }

    /**
     * Remove MCP proxy server file
     *
     * @return bool
     */
    public static function remove_proxy_file(): bool {
        $proxy_path = self::get_proxy_file_path();

        if (file_exists($proxy_path)) {
            return unlink($proxy_path);
        }

        return true; // File doesn't exist, consider it "removed"
    }

    /**
     * Get proxy file path
     *
     * @return string
     */
    public static function get_proxy_file_path(): string {
        return WP_CONTENT_DIR . '/plugins/woo-mcp/mcp-proxy.js';
    }

    /**
     * Get Claude Desktop setup instructions
     *
     * @return array
     */
    public static function get_claude_setup_instructions(): array {
        $proxy_js_path = self::get_proxy_file_path();
        $proxy_php_path = str_replace('.js', '.php', $proxy_js_path);
        
        $php_config = array(
            'mcpServers' => array(
                'woocommerce' => array(
                    'command' => 'php',
                    'args' => array($proxy_php_path)
                )
            )
        );

        $node_config = array(
            'mcpServers' => array(
                'woocommerce' => array(
                    'command' => 'node',
                    'args' => array($proxy_js_path)
                )
            )
        );

        return array(
            'proxyPath' => $proxy_js_path,
            'phpProxyPath' => $proxy_php_path,
            'phpConfig' => json_encode($php_config, JSON_PRETTY_PRINT),
            'nodeConfig' => json_encode($node_config, JSON_PRETTY_PRINT),
            'config' => json_encode($node_config, JSON_PRETTY_PRINT) // backward compatibility
        );
    }

    /**
     * Generate proxy server JavaScript content
     *
     * @return string
     */
    private static function generate_proxy_content(): string {
        $site_url = home_url();
        $mcp_endpoint = rest_url('wp/v2/wpmcp/streamable');
        $generated_time = current_time('c');

        return <<<JS
#!/usr/bin/env node
/**
 * MCP Proxy Server for WordPress MCP Plugin
 * Automatically generated - connects Claude.ai Desktop to WordPress MCP endpoints
 *
 * Generated on: {$generated_time}
 * WordPress Site: {$site_url}
 * MCP Endpoint: {$mcp_endpoint}
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { ListToolsRequestSchema, CallToolRequestSchema } from '@modelcontextprotocol/sdk/types.js';

const WORDPRESS_MCP_URL = '{$mcp_endpoint}';

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
        return { content: [{ type: 'text', text: `Error: \${error.message}` }] };
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
      throw new Error(`HTTP \${response.status}: \${response.statusText}`);
    }

    const data = await response.json();
    
    if (data.error) {
      throw new Error(`MCP Error: \${data.error.message}`);
    }

    return data;
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error('[MCP Proxy] WordPress MCP Proxy Server running');
  }
}

const server = new McpProxy();
server.run().catch(console.error);
JS;
    }
}
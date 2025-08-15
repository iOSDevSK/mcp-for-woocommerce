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

        // Make file executable using WP_Filesystem
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $wp_filesystem->chmod( $proxy_path, 0755 );

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
            return wp_delete_file($proxy_path);
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

        $js_content = '#!/usr/bin/env node' . "\n" .
'/**' . "\n" .
' * MCP Proxy Server for WordPress MCP Plugin' . "\n" .
' * Automatically generated - connects Claude.ai Desktop to WordPress MCP endpoints' . "\n" .
' *' . "\n" .
' * Generated on: ' . $generated_time . "\n" .
' * WordPress Site: ' . $site_url . "\n" .
' * MCP Endpoint: ' . $mcp_endpoint . "\n" .
' */' . "\n" . "\n" .
'import { Server } from \'@modelcontextprotocol/sdk/server/index.js\';' . "\n" .
'import { StdioServerTransport } from \'@modelcontextprotocol/sdk/server/stdio.js\';' . "\n" .
'import { ListToolsRequestSchema, CallToolRequestSchema } from \'@modelcontextprotocol/sdk/types.js\';' . "\n" . "\n" .
'const WORDPRESS_MCP_URL = \'' . $mcp_endpoint . '\';' . "\n" . "\n" .
'class McpProxy {' . "\n" .
'  constructor() {' . "\n" .
'    this.server = new Server(' . "\n" .
'      {' . "\n" .
'        name: \'woocommerce-mcp-proxy\',' . "\n" .
'        version: \'1.0.0\',' . "\n" .
'      },' . "\n" .
'      {' . "\n" .
'        capabilities: {' . "\n" .
'          tools: {},' . "\n" .
'        },' . "\n" .
'      }' . "\n" .
'    );' . "\n" . "\n" .
'    this.setupHandlers();' . "\n" .
'    this.setupErrorHandling();' . "\n" .
'  }' . "\n" . "\n" .
'  setupErrorHandling() {' . "\n" .
'    this.server.onerror = (error) => {' . "\n" .
'      console.error(\'[MCP Proxy] Server error:\', error);' . "\n" .
'    };' . "\n" . "\n" .
'    process.on(\'SIGINT\', async () => {' . "\n" .
'      await this.server.close();' . "\n" .
'      process.exit(0);' . "\n" .
'    });' . "\n" .
'  }' . "\n" . "\n" .
'  setupHandlers() {' . "\n" .
'    // Proxy tools/list requests' . "\n" .
'    this.server.setRequestHandler(ListToolsRequestSchema, async () => {' . "\n" .
'      try {' . "\n" .
'        const response = await this.forwardRequest(\'tools/list\', {});' . "\n" .
'        return response.result || { tools: [] };' . "\n" .
'      } catch (error) {' . "\n" .
'        console.error(\'Error forwarding tools/list:\', error);' . "\n" .
'        return { tools: [] };' . "\n" .
'      }' . "\n" .
'    });' . "\n" . "\n" .
'    // Proxy tools/call requests' . "\n" .
'    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {' . "\n" .
'      try {' . "\n" .
'        const response = await this.forwardRequest(\'tools/call\', request.params);' . "\n" .
'        return response.result || { content: [{ type: \'text\', text: \'Error executing tool\' }] };' . "\n" .
'      } catch (error) {' . "\n" .
'        console.error(\'Error forwarding tools/call:\', error);' . "\n" .
'        return { content: [{ type: \'text\', text: `Error: ${error.message}` }] };' . "\n" .
'      }' . "\n" .
'    });' . "\n" .
'  }' . "\n" . "\n" .
'  async forwardRequest(method, params) {' . "\n" .
'    const requestBody = {' . "\n" .
'      jsonrpc: \'2.0\',' . "\n" .
'      id: Math.random().toString(36).substring(7),' . "\n" .
'      method: method,' . "\n" .
'      params: params || {}' . "\n" .
'    };' . "\n" . "\n" .
'    const headers = {' . "\n" .
'      \'Content-Type\': \'application/json\',' . "\n" .
'      \'Accept\': \'application/json, text/event-stream\'' . "\n" .
'    };' . "\n" . "\n" .
'    const response = await fetch(WORDPRESS_MCP_URL, {' . "\n" .
'      method: \'POST\',' . "\n" .
'      headers: headers,' . "\n" .
'      body: JSON.stringify(requestBody)' . "\n" .
'    });' . "\n" . "\n" .
'    if (!response.ok) {' . "\n" .
'      throw new Error(`HTTP ${response.status}: ${response.statusText}`);' . "\n" .
'    }' . "\n" . "\n" .
'    const data = await response.json();' . "\n" .
'    ' . "\n" .
'    if (data.error) {' . "\n" .
'      throw new Error(`MCP Error: ${data.error.message}`);' . "\n" .
'    }' . "\n" . "\n" .
'    return data;' . "\n" .
'  }' . "\n" . "\n" .
'  async run() {' . "\n" .
'    const transport = new StdioServerTransport();' . "\n" .
'    await this.server.connect(transport);' . "\n" .
'    console.error(\'[MCP Proxy] WordPress MCP Proxy Server running\');' . "\n" .
'  }' . "\n" .
'}' . "\n" . "\n" .
'const server = new McpProxy();' . "\n" .
'server.run().catch(console.error);';

        return $js_content;
    }
}
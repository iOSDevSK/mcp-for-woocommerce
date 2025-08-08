<?php
declare(strict_types=1);

namespace Automattic\WordpressMcp\Core;

/**
 * PHP MCP Proxy Server
 * Pure PHP implementation of MCP proxy for Claude.ai Desktop integration
 * No Node.js dependencies required
 */
class McpPhpProxy {
    
    private string $wordpress_mcp_url;
    private array $server_info;
    private int $request_id = 0;
    
    public function __construct(string $wordpress_mcp_url) {
        $this->wordpress_mcp_url = $wordpress_mcp_url;
        $this->server_info = [
            'name' => 'woocommerce-mcp-php-proxy',
            'version' => '1.0.0'
        ];
    }
    
    /**
     * Main proxy server loop - handles STDIO communication
     */
    public function run(): void {
        // Set unbuffered output for real-time communication
        stream_set_blocking(STDIN, false);
        stream_set_blocking(STDOUT, false);
        
        // Error log to stderr for debugging
        error_log("[PHP MCP Proxy] Starting WordPress MCP Proxy Server");
        
        // Send server info to stderr for debugging
        error_log("[PHP MCP Proxy] Connecting to: " . $this->wordpress_mcp_url);
        
        while (true) {
            $input = fgets(STDIN);
            if ($input === false) {
                usleep(10000); // 10ms sleep to prevent busy waiting
                continue;
            }
            
            $input = trim($input);
            if (empty($input)) {
                continue;
            }
            
            try {
                $request = json_decode($input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("[PHP MCP Proxy] Invalid JSON: " . $input);
                    continue;
                }
                
                $response = $this->handleRequest($request);
                if ($response !== null) {
                    echo json_encode($response) . "\n";
                    flush();
                }
                
            } catch (\Exception $e) {
                error_log("[PHP MCP Proxy] Error: " . $e->getMessage());
                $error_response = [
                    'jsonrpc' => '2.0',
                    'id' => $request['id'] ?? 0,
                    'error' => [
                        'code' => -32603,
                        'message' => $e->getMessage()
                    ]
                ];
                echo json_encode($error_response) . "\n";
                flush();
            }
        }
    }
    
    /**
     * Handle MCP request and route to appropriate handler
     */
    private function handleRequest(array $request): ?array {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? 0;
        
        error_log("[PHP MCP Proxy] Handling method: " . $method);
        
        switch ($method) {
            case 'initialize':
                return $this->handleInitialize($id, $params);
                
            case 'notifications/initialized':
                // No response needed for notifications
                return null;
                
            case 'tools/list':
                return $this->proxyRequest('tools/list', $params, $id);
                
            case 'tools/call':
                return $this->proxyRequest('tools/call', $params, $id);
                
            case 'resources/list':
                return $this->proxyRequest('resources/list', $params, $id);
                
            case 'resources/read':
                return $this->proxyRequest('resources/read', $params, $id);
                
            case 'prompts/list':
                return $this->proxyRequest('prompts/list', $params, $id);
                
            case 'prompts/get':
                return $this->proxyRequest('prompts/get', $params, $id);
                
            default:
                error_log("[PHP MCP Proxy] Unknown method: " . $method);
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => -32601,
                        'message' => 'Method not found: ' . $method
                    ]
                ];
        }
    }
    
    /**
     * Handle initialization request
     */
    private function handleInitialize(int $id, array $params): array {
        error_log("[PHP MCP Proxy] Initialize request received");
        
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => new \stdClass(),
                    'resources' => new \stdClass(),
                    'prompts' => new \stdClass()
                ],
                'serverInfo' => $this->server_info
            ]
        ];
    }
    
    /**
     * Proxy request to WordPress MCP endpoint
     */
    private function proxyRequest(string $method, array $params, int $id): array {
        $request_body = [
            'jsonrpc' => '2.0',
            'id' => ++$this->request_id,
            'method' => $method,
            'params' => $params
        ];
        
        error_log("[PHP MCP Proxy] Proxying to WordPress: " . $method);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json, text/event-stream'
                ],
                'content' => json_encode($request_body),
                'timeout' => 30
            ]
        ]);
        
        $response = file_get_contents($this->wordpress_mcp_url, false, $context);
        
        if ($response === false) {
            error_log("[PHP MCP Proxy] HTTP request failed to: " . $this->wordpress_mcp_url);
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => 'Failed to connect to WordPress MCP endpoint'
                ]
            ];
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[PHP MCP Proxy] Invalid JSON response: " . $response);
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => 'Invalid JSON response from WordPress'
                ]
            ];
        }
        
        // Forward the result/error with original request ID
        if (isset($data['result'])) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $data['result']
            ];
        } elseif (isset($data['error'])) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => $data['error']
            ];
        } else {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => 'Unexpected response format from WordPress'
                ]
            ];
        }
    }
}
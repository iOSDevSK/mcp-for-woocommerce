#!/usr/bin/env php
<?php
/**
 * PHP MCP Proxy Server for WordPress MCP Plugin
 * Pure PHP implementation - no Node.js dependencies required
 *
 * Usage: php mcp-proxy.php
 * For Claude Desktop, configure in claude_desktop_config.json:
 * {
 *   "mcpServers": {
 *     "woocommerce": {
 *       "command": "php",
 *       "args": ["/path/to/mcp-for-woocommerce/mcp-proxy.php"]
 *     }
 *   }
 * }
 */

declare(strict_types=1);

// WordPress MCP endpoint URL
const MCPFOWO_URL = 'https://woo.webtalkbot.com/wp-json/wp/v2/wpmcp/streamable';

/**
 * Simple PHP MCP Proxy - standalone implementation
 */
class McpPhpProxyStandalone {
    
    private string $mcpfowo_url;
    private array $server_info;
    private int $request_id = 0;
    
    public function __construct(string $mcpfowo_url) {
        $this->mcpfowo_url = $mcpfowo_url;
        $this->server_info = [
            'name' => 'woocommerce-mcp-php-proxy',
            'version' => '1.0.0'
        ];
    }
    
    /**
     * Main proxy server loop - handles STDIO communication
     */
    public function run(): void {
        // Error log to stderr for debugging
        
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
                    continue;
                }
                
                $response = $this->handleRequest($request);
                if ($response !== null) {
                    echo json_encode($response) . "\n";
                    flush();
                }
                
            } catch (\Exception $e) {
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
        
        $start_time = microtime(true);
        $this->logConnectionAttempt($method, $request_body);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json, text/event-stream',
                    'User-Agent: WooMCP-PHP-Proxy/1.0'
                ],
                'content' => json_encode($request_body),
                'timeout' => 30
            ]
        ]);
        
        $response = file_get_contents($this->mcpfowo_url, false, $context);
        $duration = round((microtime(true) - $start_time) * 1000, 2);
        
        if ($response === false) {
            $this->logConnectionFailure($method, 'HTTP request failed', $duration);
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
            $this->logConnectionFailure($method, 'Invalid JSON response: ' . json_last_error_msg(), $duration);
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => 'Invalid JSON response from WordPress'
                ]
            ];
        }
        
        // Log successful connection
        $this->logConnectionSuccess($method, $data, $duration);
        
        // Forward the result/error with original request ID
        if (isset($data['result'])) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $data['result']
            ];
        } elseif (isset($data['error'])) {
            // Ensure error code is integer for Claude Desktop compatibility
            $error = $data['error'];
            if (isset($error['code']) && !is_int($error['code'])) {
                $error['code'] = (int)$error['code'];
            }
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => $error
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
    
    /**
     * Log connection attempt
     */
    private function logConnectionAttempt(string $method, array $request_body): void {
        $log_data = [
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'event' => 'proxy_connection_attempt',
            'method' => $method,
            'endpoint' => $this->mcpfowo_url,
            'request_body' => $request_body,
            'pid' => getmypid()
        ];
        
    }
    
    /**
     * Log connection failure
     */
    private function logConnectionFailure(string $method, string $error, float $duration): void {
        $log_data = [
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'event' => 'proxy_connection_failure',
            'method' => $method,
            'endpoint' => $this->mcpfowo_url,
            'error' => $error,
            'duration_ms' => $duration,
            'pid' => getmypid()
        ];
        
    }
    
    /**
     * Log connection success
     */
    private function logConnectionSuccess(string $method, array $response, float $duration): void {
        $log_data = [
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'event' => 'proxy_connection_success',
            'method' => $method,
            'endpoint' => $this->mcpfowo_url,
            'success' => isset($response['result']),
            'has_error' => isset($response['error']),
            'error_code' => isset($response['error']['code']) ? $response['error']['code'] : null,
            'duration_ms' => $duration,
            'pid' => getmypid()
        ];
        
    }
}

// Run the proxy server
$proxy = new McpPhpProxyStandalone(MCPFOWO_URL);
$proxy->run();
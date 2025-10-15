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
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        }
        
        // Send server info to stderr for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        }
        
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
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    }
                    continue;
                }
                
                $response = $this->handleRequest($request);
                if ($response !== null) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-RPC protocol output to STDOUT
                    echo wp_json_encode($response) . "\n";
                    flush();
                }
                
            } catch (\Exception $e) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                }
                $error_response = [
                    'jsonrpc' => '2.0',
                    'id' => $request['id'] ?? 0,
                    'error' => [
                        'code' => -32603,
                        'message' => $e->getMessage()
                    ]
                ];
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-RPC protocol output to STDOUT
                echo wp_json_encode($error_response) . "\n";
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
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        }
        
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
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                }
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
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        }
        
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
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        }
        
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
        
        $response = wp_remote_get( $this->wordpress_mcp_url, [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json, text/event-stream'
            ],
            'body' => json_encode( $request_body ),
            'timeout' => 30
        ] );
        
        if ( is_wp_error( $response ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'MCP Proxy Error: ' . $response->get_error_message() );
            }
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => 'Failed to connect to WordPress MCP endpoint'
                ]
            ];
        }

        $response_body = wp_remote_retrieve_body( $response );
        $data = json_decode( $response_body, true );
        if (json_last_error() !== JSON_ERROR_NONE) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            }
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
}
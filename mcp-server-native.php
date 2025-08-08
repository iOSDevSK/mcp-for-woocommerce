#!/usr/bin/env php
<?php
/**
 * WordPress MCP Server - Native PHP Implementation
 * Implements MCP protocol directly instead of proxying
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configuration
$wordpress_url = 'https://woo.webtalkbot.com';
$endpoint_url = $wordpress_url . '/wp-json/wp/v2/wpmcp/streamable';

// Native MCP Server Implementation
class NativeMcpServer {
    private string $endpoint_url;
    private array $tools_cache = [];
    
    public function __construct(string $endpoint_url) {
        $this->endpoint_url = $endpoint_url;
    }
    
    public function run(): void {
        $this->log('[Native MCP Server] Starting Native WordPress MCP Server');
        $this->log('[Native MCP Server] Endpoint: ' . $this->endpoint_url);
        
        // Read from stdin in blocking mode
        while (($line = fgets(STDIN)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $this->log('[Native MCP Server] Received: ' . substr($line, 0, 100) . '...');
            
            try {
                $request = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->log('[Native MCP Server] JSON decode error: ' . json_last_error_msg());
                    continue;
                }
                
                $response = $this->handleRequest($request);
                if ($response !== null) {
                    echo json_encode($response) . "\n";
                    fflush(STDOUT);
                    $this->log('[Native MCP Server] Sent response for: ' . ($request['method'] ?? 'unknown'));
                }
                
            } catch (Exception $e) {
                $this->log('[Native MCP Server] Error: ' . $e->getMessage());
                $request_id = $request['id'] ?? null;
                
                // Only send error response if request had an ID (not for notifications)
                if ($request_id !== null) {
                    $error_response = [
                        'jsonrpc' => '2.0',
                        'id' => (string)$request_id,
                        'error' => [
                            'code' => -32603,
                            'message' => 'Internal error: ' . $e->getMessage()
                        ]
                    ];
                    echo json_encode($error_response) . "\n";
                    fflush(STDOUT);
                }
            }
        }
        
        $this->log('[Native MCP Server] STDIN closed, server shutting down...');
    }
    
    private function handleRequest(array $request): ?array {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;
        
        $this->log('[Native MCP Server] Handling method: ' . $method);
        
        // Handle notifications (no response needed)
        if (strpos($method, 'notifications/') === 0) {
            $this->log('[Native MCP Server] Received notification: ' . $method . ' - no response needed');
            return null; // No response for notifications
        }
        
        switch ($method) {
            case 'initialize':
                return $this->handleInitialize($request, $params, $id);
            case 'tools/list':
                return $this->handleToolsList($id);
            case 'tools/call':
                return $this->handleToolsCall($params, $id);
            default:
                // Only send error response if ID is present (not for notifications)
                if ($id !== null) {
                    return [
                        'jsonrpc' => '2.0',
                        'id' => (string)$id, // Ensure ID is string
                        'error' => [
                            'code' => -32601,
                            'message' => "Method '$method' not found"
                        ]
                    ];
                }
                return null; // No response needed
        }
    }
    
    private function handleInitialize(array $request, array $params, $id): array {
        $client_protocol = $params['protocolVersion'] ?? '2025-06-18';
        
        $this->log('[Native MCP Server] Initialize - client protocol: ' . $client_protocol);
        
        $response = [
            'jsonrpc' => '2.0',
            'id' => (string)$id, // Ensure ID is string
            'result' => [
                'protocolVersion' => $client_protocol,
                'serverInfo' => [
                    'name' => 'WordPress MCP Server (Native)',
                    'version' => '1.0.0'
                ],
                'capabilities' => [
                    'tools' => [
                        'list' => true,
                        'call' => true
                    ]
                ]
            ]
        ];
        
        // After sending response, send initialized notification
        $this->sendNotificationAsync('initialized', []);
        
        return $response;
    }
    
    private function sendNotificationAsync(string $method, array $params): void {
        // Send notification in next tick to avoid blocking
        register_shutdown_function(function() use ($method, $params) {
            $notification = [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params
            ];
            echo json_encode($notification) . "\n";
            fflush(STDOUT);
            $this->log('[Native MCP Server] Sent notification: ' . $method);
        });
    }
    
    private function handleToolsList($id): array {
        $this->log('[Native MCP Server] Getting tools list from WordPress');
        
        try {
            $tools = $this->getToolsFromWordPress();
            
            return [
                'jsonrpc' => '2.0',
                'id' => (string)$id, // Ensure ID is string
                'result' => [
                    'tools' => $tools
                ]
            ];
        } catch (Exception $e) {
            return [
                'jsonrpc' => '2.0',
                'id' => (string)$id, // Ensure ID is string  
                'error' => [
                    'code' => -32603,
                    'message' => 'Failed to get tools: ' . $e->getMessage()
                ]
            ];
        }
    }
    
    private function handleToolsCall(array $params, $id): array {
        $tool_name = $params['name'] ?? '';
        $tool_arguments = $params['arguments'] ?? [];
        
        $this->log('[Native MCP Server] Calling tool: ' . $tool_name . ' with args: ' . json_encode($tool_arguments));
        
        try {
            $result = $this->callWordPressTool($tool_name, $tool_arguments);
            
            // Format result according to MCP tools/call spec
            $content = [];
            
            if (is_array($result) || is_object($result)) {
                $content[] = [
                    'type' => 'text',
                    'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ];
            } else {
                $content[] = [
                    'type' => 'text', 
                    'text' => (string)$result
                ];
            }
            
            return [
                'jsonrpc' => '2.0',
                'id' => (string)$id, // Ensure ID is string
                'result' => [
                    'content' => $content,
                    'isError' => false
                ]
            ];
        } catch (Exception $e) {
            $this->log('[Native MCP Server] Tool call error: ' . $e->getMessage());
            return [
                'jsonrpc' => '2.0',
                'id' => (string)$id, // Ensure ID is string
                'error' => [
                    'code' => -32603,
                    'message' => 'Tool call failed: ' . $e->getMessage()
                ]
            ];
        }
    }
    
    private function getToolsFromWordPress(): array {
        if (!empty($this->tools_cache)) {
            return $this->tools_cache;
        }
        
        $wordpress_request = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => []
        ];
        
        $response = $this->makeWordPressRequest($wordpress_request);
        
        if (isset($response['error'])) {
            throw new Exception('WordPress error: ' . $response['error']['message']);
        }
        
        $this->tools_cache = $response['result']['tools'] ?? [];
        return $this->tools_cache;
    }
    
    private function callWordPressTool(string $toolName, array $arguments): mixed {
        $wordpress_request = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $toolName,
                'arguments' => $arguments
            ]
        ];
        
        $this->log('[Native MCP Server] Sending to WordPress: ' . json_encode($wordpress_request));
        
        $response = $this->makeWordPressRequest($wordpress_request);
        
        $this->log('[Native MCP Server] WordPress response: ' . json_encode($response));
        
        if (isset($response['error'])) {
            $error = $response['error'];
            $error_msg = is_array($error) ? 
                         ($error['message'] ?? 'Unknown error') : 
                         (string)$error;
            
            // Handle permission errors gracefully  
            if (is_array($error) && isset($error['data']['status']) && $error['data']['status'] == 403) {
                $error_msg = "Permission denied for tool '$toolName'. This tool may require authentication.";
            }
            
            throw new Exception('WordPress error: ' . $error_msg);
        }
        
        return $response['result'] ?? null;
    }
    
    private function makeWordPressRequest(array $request): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json, text/event-stream'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($curl_error)) {
            throw new Exception('CURL error: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('HTTP error: ' . $http_code);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    private function log(string $message): void {
        if (defined('STDERR')) {
            fwrite(STDERR, $message . "\n");
        } else {
            error_log($message);
        }
    }
}

// Start the server
$server = new NativeMcpServer($endpoint_url);
$server->run();
#!/usr/bin/env php
<?php
/**
 * WordPress MCP Server - PHP Implementation
 * Automatically connects to WordPress when JWT is disabled
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configuration
$wordpress_url = 'https://woo.webtalkbot.com';
$endpoint_url = $wordpress_url . '/wp-json/wp/v2/wpmcp/streamable';

// MCP Server Implementation
class McpPhpServer {
    private string $endpoint_url;
    private int $request_id = 1;
    
    public function __construct(string $endpoint_url) {
        $this->endpoint_url = $endpoint_url;
    }
    
    public function run(): void {
        // Send initialize capabilities
        $this->log('[PHP MCP Server] Starting WordPress MCP PHP Server');
        $this->log('[PHP MCP Server] Connecting to: ' . $this->endpoint_url);
        
        // Read from stdin with non-blocking mode
        $this->log('[PHP MCP Server] Starting STDIN loop...');
        stream_set_blocking(STDIN, false);
        
        while (true) {
            $line = fgets(STDIN);
            
            if ($line === false) {
                if (feof(STDIN)) {
                    $this->log('[PHP MCP Server] STDIN EOF detected, exiting...');
                    break;
                }
                // No data available, wait and continue
                usleep(100000); // 100ms
                continue;
            }
            $line = trim($line);
            if (empty($line)) {
                $this->log('[PHP MCP Server] Empty line received, continuing...');
                continue;
            }
            
            $this->log('[PHP MCP Server] Received: ' . substr($line, 0, 100) . '...');
            
            try {
                $request = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->log('[PHP MCP Server] JSON decode error: ' . json_last_error_msg());
                    continue;
                }
                
                $response = $this->handleRequest($request);
                if ($response) {
                    echo json_encode($response) . "\n";
                    fflush(STDOUT);
                    $this->log('[PHP MCP Server] Sent response for: ' . ($request['method'] ?? 'unknown'));
                    
                    // Continue processing - don't exit after initialize
                    if (($request['method'] ?? '') === 'initialize') {
                        $this->log('[PHP MCP Server] Initialize complete, waiting for more requests...');
                    }
                }
                
            } catch (Exception $e) {
                $this->log('[PHP MCP Server] Error: ' . $e->getMessage());
                $error_response = [
                    'jsonrpc' => '2.0',
                    'id' => $request['id'] ?? null,
                    'error' => [
                        'code' => -32603,
                        'message' => 'Internal error: ' . $e->getMessage()
                    ]
                ];
                echo json_encode($error_response) . "\n";
                fflush(STDOUT);
            }
        }
        
        $this->log('[PHP MCP Server] STDIN closed, server shutting down...');
    }
    
    private function handleRequest(array $request): ?array {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;
        
        $this->log('[PHP MCP Server] Handling method: ' . $method);
        
        // Handle initialize specially to match protocol versions
        if ($method === 'initialize') {
            $client_protocol = $params['protocolVersion'] ?? '2025-06-18';
            $this->log('[PHP MCP Server] Client protocol version: ' . $client_protocol);
        }
        
        // Forward request to WordPress
        try {
            $wordpress_request = [
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => $method,
                'params' => $params
            ];
            
            $response_data = $this->makeWordPressRequest($wordpress_request);
            
            if (isset($response_data['error'])) {
                // Ensure error code is integer for Claude Desktop compatibility  
                $error = $response_data['error'];
                if (isset($error['code']) && !is_int($error['code'])) {
                    $error['code'] = (int)$error['code'];
                }
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => $error
                ];
            } elseif (isset($response_data['result'])) {
                $result = $response_data['result'];
                
                // Fix protocol version and capabilities mismatch for initialize
                if ($method === 'initialize' && isset($result['protocolVersion'])) {
                    $client_protocol = $params['protocolVersion'] ?? '2025-06-18';
                    $result['protocolVersion'] = $client_protocol; // Match client's version
                    $this->log('[PHP MCP Server] Updated protocol version to: ' . $client_protocol);
                    
                    // Match client capabilities - if client sends empty, respond with empty
                    $client_capabilities = $params['capabilities'] ?? [];
                    if (empty($client_capabilities)) {
                        $result['capabilities'] = [
                            'tools' => [],
                            'resources' => [],
                            'prompts' => []
                        ];
                        $this->log('[PHP MCP Server] Using minimal capabilities to match client');
                    }
                }
                
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => $result
                ];
            } else {
                throw new Exception('Invalid WordPress response format');
            }
            
        } catch (Exception $e) {
            $this->log('[PHP MCP Server] WordPress request failed: ' . $e->getMessage());
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => 'Failed to connect to WordPress: ' . $e->getMessage()
                ]
            ];
        }
    }
    
    private function makeWordPressRequest(array $request): array {
        $start_time = microtime(true);
        $method = $request['method'] ?? 'unknown';
        
        $this->logDetailedConnectionAttempt($method, $request);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json, text/event-stream',
                'User-Agent: WooMCP-PHP-Server/1.0'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_VERBOSE => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_info = curl_getinfo($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        $duration = round((microtime(true) - $start_time) * 1000, 2);
        
        if ($response === false || !empty($curl_error)) {
            $this->logDetailedConnectionFailure($method, 'CURL error: ' . $curl_error, $duration, $curl_info);
            throw new Exception('CURL error: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            $this->logDetailedConnectionFailure($method, 'HTTP error: ' . $http_code, $duration, $curl_info, substr($response, 0, 500));
            throw new Exception('HTTP error: ' . $http_code . ' - ' . substr($response, 0, 200));
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logDetailedConnectionFailure($method, 'JSON decode error: ' . json_last_error_msg(), $duration, $curl_info, substr($response, 0, 500));
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }
        
        $this->logDetailedConnectionSuccess($method, $data, $duration, $curl_info);
        
        return $data;
    }
    
    private function logDetailedConnectionAttempt(string $method, array $request): void {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'server_connection_attempt',
            'method' => $method,
            'endpoint' => $this->endpoint_url,
            'request_id' => $request['id'] ?? null,
            'has_params' => !empty($request['params']),
            'pid' => getmypid()
        ];
        
        $this->log('[PHP MCP Server] CONNECTION_ATTEMPT: ' . json_encode($log_data));
    }
    
    private function logDetailedConnectionFailure(string $method, string $error, float $duration, array $curl_info, string $response = ''): void {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'server_connection_failure',
            'method' => $method,
            'endpoint' => $this->endpoint_url,
            'error' => $error,
            'duration_ms' => $duration,
            'http_code' => $curl_info['http_code'] ?? 0,
            'total_time' => $curl_info['total_time'] ?? 0,
            'connect_time' => $curl_info['connect_time'] ?? 0,
            'response_preview' => $response ? substr($response, 0, 200) : '',
            'pid' => getmypid()
        ];
        
        $this->log('[PHP MCP Server] CONNECTION_FAILURE: ' . json_encode($log_data));
    }
    
    private function logDetailedConnectionSuccess(string $method, array $response, float $duration, array $curl_info): void {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'server_connection_success',
            'method' => $method,
            'endpoint' => $this->endpoint_url,
            'success' => isset($response['result']),
            'has_error' => isset($response['error']),
            'error_code' => isset($response['error']['code']) ? $response['error']['code'] : null,
            'duration_ms' => $duration,
            'http_code' => $curl_info['http_code'] ?? 0,
            'total_time' => $curl_info['total_time'] ?? 0,
            'pid' => getmypid()
        ];
        
        $this->log('[PHP MCP Server] CONNECTION_SUCCESS: ' . json_encode($log_data));
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
$server = new McpPhpServer($endpoint_url);
$server->run();
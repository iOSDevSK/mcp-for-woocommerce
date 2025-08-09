#!/usr/bin/env php
<?php
/**
 * Test script to verify MCP connection logging
 * This script simulates various types of connections to test logging functionality
 */

// Test endpoint
$endpoint = 'https://woo.webtalkbot.com/wp-json/wp/v2/wpmcp/streamable';

/**
 * Test different connection scenarios
 */
function testConnectionLogging() {
    $scenarios = [
        [
            'name' => 'Claude.ai Web App Connection',
            'headers' => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 claude.ai',
                'Anthropic-Beta: mcp-2025-06-18',
            ],
            'body' => json_encode([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2025-06-18',
                    'capabilities' => []
                ]
            ])
        ],
        [
            'name' => 'Claude Desktop Connection',
            'headers' => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Claude Desktop/1.0',
            ],
            'body' => json_encode([
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/list',
                'params' => []
            ])
        ],
        [
            'name' => 'MCP Proxy Connection',
            'headers' => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: WooMCP-PHP-Proxy/1.0',
            ],
            'body' => json_encode([
                'jsonrpc' => '2.0',
                'id' => 3,
                'method' => 'resources/list',
                'params' => []
            ])
        ],
        [
            'name' => 'Unknown Client Connection',
            'headers' => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: UnknownClient/1.0',
            ],
            'body' => json_encode([
                'jsonrpc' => '2.0',
                'id' => 4,
                'method' => 'prompts/list',
                'params' => []
            ])
        ],
        [
            'name' => 'Invalid Request (should fail)',
            'headers' => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: TestClient/1.0',
            ],
            'body' => '{"invalid": "json"}'  // Invalid JSON-RPC
        ]
    ];

    global $endpoint;
    
    echo "Testing MCP Connection Logging\n";
    echo "==============================\n\n";
    echo "Endpoint: $endpoint\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($scenarios as $i => $scenario) {
        echo ($i + 1) . ". Testing: " . $scenario['name'] . "\n";
        echo str_repeat('-', 50) . "\n";
        
        $start_time = microtime(true);
        $result = makeTestRequest($endpoint, $scenario['headers'], $scenario['body']);
        $duration = round((microtime(true) - $start_time) * 1000, 2);
        
        echo "Duration: {$duration}ms\n";
        echo "HTTP Code: " . $result['http_code'] . "\n";
        echo "Response: " . (strlen($result['response']) > 200 ? 
            substr($result['response'], 0, 200) . '...' : 
            $result['response']) . "\n";
        
        if (!empty($result['error'])) {
            echo "Error: " . $result['error'] . "\n";
        }
        
        echo "\n";
        
        // Add small delay between requests
        usleep(500000); // 0.5 second
    }
    
    echo "Test completed. Check the following log files for detailed connection logs:\n";
    echo "- WordPress debug.log\n";
    echo "- wp-content/mcp-claude-debug.log\n";
    echo "- wp-content/mcp-connections.log\n";
    echo "- wp-content/mcp-claude-connections.log\n";
    echo "- wp-content/mcp-connection-failures.log\n\n";
}

/**
 * Make a test HTTP request
 */
function makeTestRequest(string $url, array $headers, string $body): array {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_VERBOSE => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response !== false ? $response : '',
        'http_code' => $http_code,
        'error' => $error
    ];
}

// Run the test
testConnectionLogging();
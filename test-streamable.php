<?php
/**
 * Test script for HTTP Streamable transport
 * This script tests the new streaming functionality
 */

// Test large batch request to verify streaming
$large_batch_request = array();
for ( $i = 1; $i <= 10; $i++ ) {
    $large_batch_request[] = array(
        'jsonrpc' => '2.0',
        'method'  => 'tools/list',
        'id'      => $i,
        'params'  => array()
    );
}

// Test endpoint
$endpoint = 'https://woo.webtalkbot.com/wp-json/wp/v2/wpmcp/streamable';

// Test headers for streamable transport
$headers = array(
    'Content-Type: application/json',
    'Accept: application/json, text/event-stream',
    'MCP-Protocol-Version: 2025-06-18'
);

echo "Testing HTTP Streamable Transport\n";
echo "Endpoint: $endpoint\n";
echo "Sending batch of " . count($large_batch_request) . " requests...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($large_batch_request));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_HEADER, true);

// Enable streaming response handling
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    echo "Received chunk: " . strlen($data) . " bytes\n";
    echo "Data: " . substr($data, 0, 100) . "...\n";
    return strlen($data);
});

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "\nResponse Status: $http_code\n";
echo "Content-Type: $content_type\n";

if (curl_error($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
}

curl_close($ch);

echo "\nStreamable transport test completed.\n";
?>
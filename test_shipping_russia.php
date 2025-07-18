<?php
/**
 * Manual test script for shipping to Russia
 * Run this to test the "do you ship to Russia?" scenario
 */

require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/includes/autoloader.php';

use Automattic\WordpressMcp\Tools\McpWooShipping;

echo "=== Testing Shipping to Russia ===\n";

// Create shipping tool instance
$shipping_tool = new McpWooShipping();

// Test 1: Russia (unsupported country)
echo "\n1. Testing shipping to Russia:\n";
try {
    $result = $shipping_tool->check_shipping_to_country(['country' => 'Russia']);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Test 2: Invalid zone ID (should throw exception)
echo "\n2. Testing invalid zone ID (-1):\n";
try {
    $result = $shipping_tool->get_shipping_zone_safe(['id' => -1]);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Exception (expected): " . $e->getMessage() . "\n";
}

// Test 3: Non-existent zone ID (should throw exception)
echo "\n3. Testing non-existent zone ID (999999):\n";
try {
    $result = $shipping_tool->get_shipping_zone_safe(['id' => 999999]);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Exception (expected): " . $e->getMessage() . "\n";
}

// Test 4: Valid zone ID (should work)
echo "\n4. Testing valid zone ID (0 - default zone):\n";
try {
    $result = $shipping_tool->get_shipping_zone_safe(['id' => 0]);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Test 5: Unrecognized country
echo "\n5. Testing shipping to unrecognized country (Atlantis):\n";
try {
    $result = $shipping_tool->check_shipping_to_country(['country' => 'Atlantis']);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Complete ===\n";
echo "Expected results:\n";
echo "1. Russia: available=false, country recognized\n";
echo "2. Invalid zone ID: Exception thrown\n";
echo "3. Non-existent zone: Exception thrown\n";
echo "4. Valid zone: Success with zone data\n";
echo "5. Unrecognized country: available=false, country not recognized\n";
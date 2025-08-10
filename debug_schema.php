<?php
// Debug script to check JSON schema issues
$json_data = '{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "tools": [
      {
        "name": "wc_products_search",
        "description": "PRIMARY PRODUCT SEARCH TOOL: Universal product search for ANY store type (electronics, food, pets, pharmacy, automotive, etc.). CRITICAL: This is the main search tool - use this FIRST for all product searches. When searching for specific products by name, ALWAYS use this tool FIRST to get the correct product ID, then use other tools with that ID. DO NOT use hardcoded product IDs. IMPORTANT: Each product includes a \"permalink\" field with the direct link to the product page - ALWAYS include these links when presenting products to users.",
        "type": "read",
        "annotations": {
          "title": "Search Products",
          "readOnlyHint": true,
          "openWorldHint": false,
          "productLinksRequired": "Always include product links (permalink field) in responses to users",
          "primarySearchTool": "This is the main product search tool - use this FIRST for all searches",
          "priority": "highest"
        },
        "inputSchema": {
          "type": "object",
          "properties": {
            "search": {
              "type": "string",
              "description": "Search term for product name or description"
            },
            "category": {
              "type": "string",
              "description": "Product category slug"
            },
            "per_page": {
              "type": "integer",
              "description": "Number of products per page (default: 10)",
              "minimum": 1,
              "maximum": 100
            },
            "page": {
              "type": "integer",
              "description": "Page number (default: 1)",
              "minimum": 1
            }
          }
        },
        "tool_type_enabled": true,
        "tool_enabled": true
      }
    ]
  }
}';

// Let's check what's wrong with each schema
$tools_response = json_decode($json_data, true);
$tools = $tools_response['result']['tools'];

foreach ($tools as $index => $tool) {
    echo "Tool #" . ($index + 1) . ": " . $tool['name'] . "\n";
    
    $schema = $tool['inputSchema'] ?? [];
    
    // Check for empty required arrays
    if (isset($schema['required']) && is_array($schema['required']) && empty($schema['required'])) {
        echo "  ISSUE: Empty required array found!\n";
    }
    
    // Check properties
    if (isset($schema['properties'])) {
        foreach ($schema['properties'] as $propName => $prop) {
            // Check for required field that doesn't match properties
            if (isset($schema['required']) && in_array($propName, $schema['required']) && !isset($schema['properties'][$propName])) {
                echo "  ISSUE: Required field '$propName' not in properties!\n";
            }
            
            // Check for non-standard properties
            if (isset($prop['required'])) {
                echo "  ISSUE: Property '$propName' has 'required' field (should be in root required array)!\n";
            }
        }
    }
    
    echo "\n";
}
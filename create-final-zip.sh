#!/bin/bash

# Create WordPress.org ZIP package for Woo MCP plugin
# This script creates a clean ZIP package excluding development files

# Change to plugin directory
cd "/Users/filipdvoran/Developer/woo-mcp"

# Remove any existing ZIP
rm -f "woo-mcp-1.1.5.zip"

# Create temporary directory structure
mkdir -p "/tmp/woo-mcp-build/woo-mcp"

# Copy essential plugin files
cp "woo-mcp.php" "/tmp/woo-mcp-build/woo-mcp/"
cp "readme.txt" "/tmp/woo-mcp-build/woo-mcp/"
cp "uninstall.php" "/tmp/woo-mcp-build/woo-mcp/"
cp "client-setup.md" "/tmp/woo-mcp-build/woo-mcp/"

# Copy directories
cp -r "includes" "/tmp/woo-mcp-build/woo-mcp/"
cp -r "vendor" "/tmp/woo-mcp-build/woo-mcp/"
cp -r "build" "/tmp/woo-mcp-build/woo-mcp/"
cp -r "languages" "/tmp/woo-mcp-build/woo-mcp/"

# Create ZIP from the temporary directory
cd "/tmp/woo-mcp-build"
zip -r "/Users/filipdvoran/Developer/woo-mcp/woo-mcp-1.1.5.zip" "woo-mcp"

# Clean up temporary directory
rm -rf "/tmp/woo-mcp-build"

echo "ZIP package created: /Users/filipdvoran/Developer/woo-mcp/woo-mcp-1.1.5.zip"
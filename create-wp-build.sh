#!/bin/bash

# WordPress.org build script for Woo MCP plugin

# Create temporary directory
mkdir -p /tmp/woo-mcp-wp-package
cd /tmp/woo-mcp-wp-package

# Remove any existing directory
rm -rf woo-mcp

# Create the woo-mcp directory
mkdir woo-mcp

# Copy files from source, excluding development files
rsync -av \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='.gitignore' \
  --exclude='.wordpress-org' \
  --exclude='assets' \
  --exclude='src' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='webpack.config.js' \
  --exclude='README.md' \
  --exclude='CLAUDE.md' \
  --exclude='*.zip' \
  --exclude='.DS_Store' \
  --exclude='create-wp-build.sh' \
  /Users/filipdvoran/Developer/woo-mcp/ ./woo-mcp/

# Create the ZIP file
zip -r woo-mcp-1.1.5.zip woo-mcp

# Copy back to source directory
cp woo-mcp-1.1.5.zip /Users/filipdvoran/Developer/woo-mcp/

echo "WordPress.org package created: woo-mcp-1.1.5.zip"
echo "Location: /Users/filipdvoran/Developer/woo-mcp/woo-mcp-1.1.5.zip"
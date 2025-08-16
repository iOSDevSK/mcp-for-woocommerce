#!/bin/bash

# WordPress.org build script for Woo MCP plugin v1.1.5
# Creates a clean package following .distignore specifications

set -e

echo "Creating WordPress.org compatible package for Woo MCP v1.1.5..."

# Define source directory
SOURCE_DIR="/Users/filipdvoran/Developer/woo-mcp"
BUILD_DIR="/tmp/woo-mcp-wp-build"
PACKAGE_NAME="woo-mcp-1.1.5.zip"

# Clean up any existing build directory
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# Create the plugin directory
mkdir -p "$BUILD_DIR/woo-mcp"

echo "Copying essential files..."

# Copy main plugin files
cp "$SOURCE_DIR/woo-mcp.php" "$BUILD_DIR/woo-mcp/"
cp "$SOURCE_DIR/readme.txt" "$BUILD_DIR/woo-mcp/"
cp "$SOURCE_DIR/uninstall.php" "$BUILD_DIR/woo-mcp/"
cp "$SOURCE_DIR/LICENSE" "$BUILD_DIR/woo-mcp/"
cp "$SOURCE_DIR/client-setup.md" "$BUILD_DIR/woo-mcp/"
cp "$SOURCE_DIR/changelog.txt" "$BUILD_DIR/woo-mcp/"

# Copy essential directories
echo "Copying includes directory..."
cp -r "$SOURCE_DIR/includes" "$BUILD_DIR/woo-mcp/"

echo "Copying vendor directory (PHP dependencies)..."
cp -r "$SOURCE_DIR/vendor" "$BUILD_DIR/woo-mcp/"

echo "Copying build directory (compiled assets)..."
cp -r "$SOURCE_DIR/build" "$BUILD_DIR/woo-mcp/"

echo "Copying languages directory..."
cp -r "$SOURCE_DIR/languages" "$BUILD_DIR/woo-mcp/"

echo "Copying static files..."
cp -r "$SOURCE_DIR/static-files" "$BUILD_DIR/woo-mcp/"

# Copy MCP proxy files
cp "$SOURCE_DIR/mcp-proxy.php" "$BUILD_DIR/woo-mcp/"
cp "$SOURCE_DIR/mcp-proxy.js" "$BUILD_DIR/woo-mcp/"

# Remove old package if it exists
rm -f "$SOURCE_DIR/$PACKAGE_NAME"

# Create the ZIP package
cd "$BUILD_DIR"
echo "Creating ZIP package..."
zip -r "$PACKAGE_NAME" woo-mcp

# Copy back to source directory
cp "$PACKAGE_NAME" "$SOURCE_DIR/"

# Cleanup
rm -rf "$BUILD_DIR"

echo ""
echo "WordPress.org package created successfully!"
echo "Package: $PACKAGE_NAME"
echo "Location: $SOURCE_DIR/$PACKAGE_NAME"
echo ""
echo "Package contents:"
echo "- Root directory: woo-mcp"
echo "- PHP dependencies: vendor/"
echo "- Compiled assets: build/"
echo "- Documentation: client-setup.md"
echo "- All essential plugin files"
echo ""
echo "Ready for WordPress.org submission!"
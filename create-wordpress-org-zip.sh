#!/bin/bash

# WordPress.org ZIP creation script for Woo MCP 1.1.5
# This script creates a ZIP package suitable for WordPress.org submission

set -e  # Exit on any error

# Set script directory and project root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR"
ZIP_NAME="woo-mcp-1.1.5.zip"
TEMP_DIR="/tmp/woo-mcp-wp-org-$$"

echo "=== WordPress.org ZIP Creation for Woo MCP 1.1.5 ==="
echo "Project root: $PROJECT_ROOT"
echo "Creating: $ZIP_NAME"
echo ""

# Clean up any existing ZIP
echo "Cleaning up existing files..."
rm -f "$PROJECT_ROOT/$ZIP_NAME"

# Create temporary directory structure
echo "Creating temporary directory structure..."
mkdir -p "$TEMP_DIR/woo-mcp"

# Copy essential files
echo "Copying essential files..."

# Main plugin files
echo "  - Main plugin files"
cp "$PROJECT_ROOT/woo-mcp.php" "$TEMP_DIR/woo-mcp/"
cp "$PROJECT_ROOT/uninstall.php" "$TEMP_DIR/woo-mcp/"
cp "$PROJECT_ROOT/readme.txt" "$TEMP_DIR/woo-mcp/"
cp "$PROJECT_ROOT/changelog.txt" "$TEMP_DIR/woo-mcp/"
cp "$PROJECT_ROOT/LICENSE" "$TEMP_DIR/woo-mcp/"

# Configuration files for server builds (REQUIRED for WordPress.org)
echo "  - Configuration files for server builds"
cp "$PROJECT_ROOT/composer.json" "$TEMP_DIR/woo-mcp/"
cp "$PROJECT_ROOT/composer.lock" "$TEMP_DIR/woo-mcp/"
cp "$PROJECT_ROOT/package.json" "$TEMP_DIR/woo-mcp/"

# Client documentation
echo "  - Documentation"
cp "$PROJECT_ROOT/client-setup.md" "$TEMP_DIR/woo-mcp/"

# Proxy files
echo "  - Proxy files"
cp "$PROJECT_ROOT/mcp-proxy.js" "$TEMP_DIR/woo-mcp/"
cp "$PROJECT_ROOT/mcp-proxy.php" "$TEMP_DIR/woo-mcp/"

# Copy directories
echo "Copying directories..."
echo "  - includes/ (PHP classes)"
cp -r "$PROJECT_ROOT/includes" "$TEMP_DIR/woo-mcp/"

echo "  - vendor/ (Composer dependencies)"
cp -r "$PROJECT_ROOT/vendor" "$TEMP_DIR/woo-mcp/"

echo "  - build/ (Compiled assets)"
cp -r "$PROJECT_ROOT/build" "$TEMP_DIR/woo-mcp/"

echo "  - languages/ (Translation files)"
cp -r "$PROJECT_ROOT/languages" "$TEMP_DIR/woo-mcp/"

echo "  - static-files/ (Static assets)"
cp -r "$PROJECT_ROOT/static-files" "$TEMP_DIR/woo-mcp/"

# Create the ZIP
echo ""
echo "Creating ZIP package..."
cd "$TEMP_DIR"
zip -r "$PROJECT_ROOT/$ZIP_NAME" woo-mcp > /dev/null

# Get file count and size
FILE_COUNT=$(unzip -l "$PROJECT_ROOT/$ZIP_NAME" | tail -1 | awk '{print $2}')
ZIP_SIZE=$(ls -lh "$PROJECT_ROOT/$ZIP_NAME" | awk '{print $5}')

echo "ZIP package created successfully!"
echo "  - File: $PROJECT_ROOT/$ZIP_NAME"
echo "  - Size: $ZIP_SIZE"
echo "  - Files: $FILE_COUNT"

# Clean up
rm -rf "$TEMP_DIR"

echo ""
echo "=== Package Verification ==="
echo ""
echo "Essential files included:"
unzip -l "$PROJECT_ROOT/$ZIP_NAME" | grep -E "(composer\.json|composer\.lock|package\.json|vendor/|build/|client-setup\.md|woo-mcp\.php)" | head -10

echo ""
echo "Files that should NOT be included (verification):"
EXCLUDED_CHECK=$(unzip -l "$PROJECT_ROOT/$ZIP_NAME" | grep -E "(node_modules|\.git|src/|README\.md|CLAUDE\.md|docs/|tests/)" | wc -l)
if [ "$EXCLUDED_CHECK" -eq 0 ]; then
    echo "✅ No excluded development files found - GOOD!"
else
    echo "⚠️  Found $EXCLUDED_CHECK excluded files - please review"
fi

echo ""
echo "=== WordPress.org Submission Ready ==="
echo "The ZIP package includes:"
echo "✅ composer.json & composer.lock (for server 'composer install')"
echo "✅ package.json (for server 'npm run build')"  
echo "✅ vendor/ directory (PHP dependencies)"
echo "✅ build/ directory (compiled assets)"
echo "✅ All WordPress plugin files"
echo "✅ Client setup documentation"
echo ""
echo "Ready for WordPress.org submission!"
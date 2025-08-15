#!/bin/bash
# WordPress.org Build Script for Woo MCP

echo "Creating WordPress.org submission build..."

# Create temporary directory for clean build
BUILD_DIR="/tmp/woo-mcp-build"
rm -rf $BUILD_DIR
mkdir -p $BUILD_DIR

# Copy only production files (excluding .distignore entries)
rsync -av --exclude-from='.distignore' ./ $BUILD_DIR/

# Remove any remaining development files that might have been missed
cd $BUILD_DIR
rm -f *.zip
rm -f .DS_Store
rm -rf .claude/

# Create the final zip
cd /tmp
zip -r woo-mcp-wordpress-org.zip woo-mcp-build/ -x "*/.*"

# Move to original directory
mv woo-mcp-wordpress-org.zip /Users/filipdvoran/Developer/woo-mcp/

echo "WordPress.org build created: woo-mcp-wordpress-org.zip"
echo "Contents of the build:"
unzip -l woo-mcp-wordpress-org.zip | head -20

# Cleanup
rm -rf $BUILD_DIR
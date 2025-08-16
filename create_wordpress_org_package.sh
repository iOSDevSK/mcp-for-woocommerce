#!/bin/bash

# WordPress.org ZIP Package Creation Script for Woo MCP Plugin v1.1.5
# This script creates a complete, submission-ready package for WordPress.org

set -e  # Exit on any error

echo "🚀 Creating WordPress.org ZIP package for Woo MCP v1.1.5..."

SOURCE_DIR="/Users/filipdvoran/Developer/woo-mcp"
TEMP_DIR="/tmp/woo-mcp-wp-org-package"
TARGET_DIR="$TEMP_DIR/woo-mcp"
ZIP_FILE="$SOURCE_DIR/woo-mcp-1.1.5.zip"

# Clean up previous builds
echo "🧹 Cleaning up previous builds..."
rm -rf "$TEMP_DIR"
rm -f "$ZIP_FILE"

# Create directory structure
echo "📁 Creating package directory structure..."
mkdir -p "$TARGET_DIR"

# Copy main plugin files
echo "📄 Copying main plugin files..."
cp "$SOURCE_DIR/woo-mcp.php" "$TARGET_DIR/"
cp "$SOURCE_DIR/readme.txt" "$TARGET_DIR/"
cp "$SOURCE_DIR/LICENSE" "$TARGET_DIR/"
cp "$SOURCE_DIR/uninstall.php" "$TARGET_DIR/"
cp "$SOURCE_DIR/changelog.txt" "$TARGET_DIR/"

# Copy build configuration files (needed for WordPress.org servers)
echo "⚙️ Copying build configuration files..."
cp "$SOURCE_DIR/composer.json" "$TARGET_DIR/"
cp "$SOURCE_DIR/composer.lock" "$TARGET_DIR/"
cp "$SOURCE_DIR/package.json" "$TARGET_DIR/"

# Copy documentation
echo "📚 Copying documentation..."
cp "$SOURCE_DIR/client-setup.md" "$TARGET_DIR/"

# Copy directories
echo "📂 Copying source code directories..."
cp -r "$SOURCE_DIR/includes" "$TARGET_DIR/"
cp -r "$SOURCE_DIR/build" "$TARGET_DIR/"
cp -r "$SOURCE_DIR/vendor" "$TARGET_DIR/"
cp -r "$SOURCE_DIR/languages" "$TARGET_DIR/"
cp -r "$SOURCE_DIR/static-files" "$TARGET_DIR/"

# Create the ZIP file
echo "🗜️ Creating ZIP archive..."
cd "$TEMP_DIR"
zip -r "woo-mcp-1.1.5.zip" woo-mcp/

# Move to final location
echo "📍 Moving ZIP to source directory..."
mv "woo-mcp-1.1.5.zip" "$ZIP_FILE"

# Clean up temp directory
echo "🧹 Cleaning up temporary files..."
rm -rf "$TEMP_DIR"

# Display results
echo ""
echo "✅ WordPress.org ZIP package created successfully!"
echo "📦 Location: $ZIP_FILE"
echo "📊 Size: $(ls -lh "$ZIP_FILE" | awk '{print $5}')"

echo ""
echo "📋 Package Contents:"
echo "✓ woo-mcp.php (main plugin file)"
echo "✓ readme.txt (WordPress.org readme)"
echo "✓ LICENSE (GPL license)"
echo "✓ uninstall.php (cleanup script)"
echo "✓ changelog.txt (version history)"
echo "✓ composer.json & composer.lock (PHP dependencies)"
echo "✓ package.json (Node.js build configuration)"
echo "✓ client-setup.md (setup documentation)"
echo "✓ includes/ (PHP source code with SQL fixes)"
echo "✓ build/ (compiled JavaScript/CSS assets)"
echo "✓ vendor/ (PHP dependencies from Composer)"
echo "✓ languages/ (translation files)"
echo "✓ static-files/ (OpenAPI specifications)"

echo ""
echo "🚫 Excluded Files (as requested):"
echo "   - .git* (version control files)"
echo "   - node_modules/ (Node.js dev dependencies)"
echo "   - src/ (source files, compiled to build/)"
echo "   - package-lock.json (npm lock file)"
echo "   - docs/ (development documentation)"
echo "   - tests/ (test files)"
echo "   - security/ (security audit files)"
echo "   - README.md (GitHub readme)"
echo "   - CLAUDE.md (AI instructions)"
echo "   - CREATE_*.md (build instructions)"
echo "   - RELEASE_*.md (release documentation)"
echo "   - MCP-*.md (development docs)"
echo "   - Readme.md (duplicate readme)"
echo "   - WORDPRESS_*.md (submission guide)"
echo "   - commits.md, history.txt (version history)"
echo "   - *.sh (shell scripts)"
echo "   - assets/ (project assets)"
echo "   - pnpm-lock.yaml (pnpm lock file)"
echo "   - mkdocs.yml (documentation config)"
echo "   - mcp-proxy.* (proxy development files)"
echo "   - mcp-server-simple.js (simple server)"
echo "   - mcp-settings-fix.js (settings fix)"
echo "   - test-proxy.js (test file)"

echo ""
echo "🎯 WordPress.org Submission Ready!"
echo "   This package includes all necessary files for WordPress.org servers to:"
echo "   - Run 'composer install' for PHP dependencies"
echo "   - Run 'npm run build' for asset compilation"
echo "   - Deploy the plugin with all required components"

echo ""
echo "📝 Next Steps:"
echo "   1. Verify the ZIP file contents"
echo "   2. Upload to WordPress.org plugin directory"
echo "   3. Submit for review"

# Verify ZIP contents
echo ""
echo "🔍 ZIP Contents Verification:"
unzip -l "$ZIP_FILE" | head -20
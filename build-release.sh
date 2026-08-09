#!/bin/bash

# Complete WordPress.org Release Build Script
# This script does everything needed to create a WordPress.org compliant distribution

set -e

# Extract version from main plugin file
VERSION=$(grep "Version:" mcp-for-woocommerce.php | sed 's/.*Version:[[:space:]]*//' | tr -d ' ')
echo "🚀 Starting WordPress.org release build for version ${VERSION}..."
echo ""

# Step 1: Install production dependencies
echo "📦 Installing production dependencies..."
composer install --no-dev --optimize-autoloader --quiet
echo "✅ Composer dependencies installed"

# Step 2: Build frontend assets
echo "🏗️  Building frontend assets..."
npm run build > /dev/null 2>&1
echo "✅ Frontend assets built"

# Step 3: Create WordPress.org distribution
echo "📋 Creating WordPress.org distribution..."
./create-wordpress-org-compliant.sh > /dev/null 2>&1
echo "✅ Distribution created"

# Step 4: Show results
echo ""
echo "🎉 WordPress.org release build complete!"
echo ""
echo "📦 Generated: mcp-for-woocommerce-${VERSION}.zip"
ls -lh mcp-for-woocommerce-${VERSION}.zip
echo ""
echo "📋 Contents summary:"
echo "  ✅ vendor/autoload.php: $(unzip -l mcp-for-woocommerce-${VERSION}.zip | grep 'vendor/autoload.php' | awk '{print $1}') bytes"
echo "  ✅ includes/Core/WpMcp.php: $(unzip -l mcp-for-woocommerce-${VERSION}.zip | grep 'includes/Core/WpMcp.php' | awk '{print $1}') bytes"
echo "  ✅ build/index.js: $(unzip -l mcp-for-woocommerce-${VERSION}.zip | grep 'build/index.js' | awk '{print $1}') bytes"
echo "  ✅ client-setup.md: $(unzip -l mcp-for-woocommerce-${VERSION}.zip | grep 'client-setup.md' | awk '{print $1}') bytes"
echo ""
echo "🚀 Ready for WordPress.org submission!"
echo ""
echo "Next steps:"
echo "1. Upload mcp-for-woocommerce-${VERSION}.zip to WordPress.org"
echo "2. Or test locally: npx wp-env start && npx wp-env run cli -- wp plugin install /path/to/mcp-for-woocommerce-${VERSION}.zip --activate"
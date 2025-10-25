#!/bin/bash

# Push to Git and SVN script for MCP for WooCommerce
# Syncs changes from Git repository to WordPress.org SVN

set -e

echo "🔄 Starting Git and SVN sync..."

# Check if we're in the right directory
if [ ! -f "mcp-for-woocommerce.php" ]; then
    echo "❌ Error: Must be run from plugin root directory"
    exit 1
fi

# Get current version from plugin file
VERSION=$(grep "Version:" mcp-for-woocommerce.php | head -1 | awk '{print $3}')
echo "📦 Current version: $VERSION"

# Push to Git
echo "📤 Pushing to GitHub..."
git add .
if git diff-index --quiet HEAD --; then
    echo "✅ No changes to commit to Git"
else
    git commit -m "chore: sync changes for version $VERSION"
    git push origin main
    echo "✅ Pushed to GitHub"
fi

# Update SVN trunk
echo "📤 Updating SVN trunk..."
cd svn-temp/mcp-for-woocommerce

# Copy files to trunk (excluding build which should come from releases)
rsync -av --delete \
    --exclude='svn-temp' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='*.md' \
    --exclude='package*.json' \
    --exclude='*.sh' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    ../../ trunk/

# Check SVN status
svn status | grep -v "^?" || true

# Commit to trunk
if svn status | grep -q "^[AMD]"; then
    svn commit -m "Update trunk to version $VERSION"
    echo "✅ SVN trunk updated"

    # Ask if user wants to create a new tag
    read -p "🏷️  Create SVN tag $VERSION? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Check if tag already exists
        if svn list https://plugins.svn.wordpress.org/mcp-for-woocommerce/tags/$VERSION &>/dev/null; then
            echo "⚠️  Tag $VERSION already exists. Skipping tag creation."
        else
            svn copy https://plugins.svn.wordpress.org/mcp-for-woocommerce/trunk \
                     https://plugins.svn.wordpress.org/mcp-for-woocommerce/tags/$VERSION \
                     -m "Tagging version $VERSION"
            echo "✅ Created tag $VERSION"
        fi
    fi
else
    echo "✅ No SVN changes to commit"
fi

cd ../..

echo "🎉 All done! Git and SVN are in sync."
echo "📋 Summary:"
echo "   - Git: main branch"
echo "   - SVN: trunk updated"
echo "   - Version: $VERSION"

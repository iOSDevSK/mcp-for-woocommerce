#!/bin/bash

# WordPress.org Compliant Distribution Build Script
# Creates mcp-for-woocommerce-{VERSION}.zip with proper directory structure

set -e

PLUGIN_SLUG="mcp-for-woocommerce"
# Extract version from main plugin file
VERSION=$(grep "Version:" mcp-for-woocommerce.php | sed 's/.*Version:[[:space:]]*//' | tr -d ' ')
BUILD_DIR="/tmp/${PLUGIN_SLUG}-${VERSION}"
FINAL_ZIP="${PLUGIN_SLUG}-${VERSION}.zip"

echo "Creating WordPress.org compliant distribution for ${PLUGIN_SLUG} v${VERSION}"

# Clean up any existing build
rm -rf "${BUILD_DIR}"
rm -f "${FINAL_ZIP}"

# Create build directory
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}"

# Copy files respecting .distignore
echo "Copying plugin files..."

# Essential plugin files
cp mcp-for-woocommerce.php "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp readme.txt "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp changelog.txt "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp uninstall.php "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp LICENSE "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp composer.json "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp composer.lock "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp package.json "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp client-setup.md "${BUILD_DIR}/${PLUGIN_SLUG}/"

# Copy directories (excluding those in .distignore)
# Create includes directory and copy contents properly
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}/includes"
cp -r includes/* "${BUILD_DIR}/${PLUGIN_SLUG}/includes/"
# Create languages directory and copy contents properly  
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}/languages"
cp -r languages/* "${BUILD_DIR}/${PLUGIN_SLUG}/languages/"
# Create vendor directory and copy contents properly
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}/vendor"
cp -r vendor/* "${BUILD_DIR}/${PLUGIN_SLUG}/vendor/"
# Create build directory and copy contents properly
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}/build"
cp -r build/* "${BUILD_DIR}/${PLUGIN_SLUG}/build/"
# Create static-files directory and copy contents properly
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}/static-files"
cp -r static-files/* "${BUILD_DIR}/${PLUGIN_SLUG}/static-files/"

# Copy PHP proxy files
cp mcp-proxy.php "${BUILD_DIR}/${PLUGIN_SLUG}/"

# Remove any .DS_Store files
find "${BUILD_DIR}" -name ".DS_Store" -delete

# Remove any debug code files that might have been copied
find "${BUILD_DIR}" -name "*.log" -delete

echo "Build directory created at: ${BUILD_DIR}"

# Create the zip file
cd "${BUILD_DIR}"
zip -r "../${FINAL_ZIP}" "${PLUGIN_SLUG}/"
cd - > /dev/null

# Move the final zip to current directory
mv "/tmp/${FINAL_ZIP}" "./"

# Clean up build directory
rm -rf "${BUILD_DIR}"

echo "WordPress.org compliant distribution created: ${FINAL_ZIP}"
echo "Directory structure: ${PLUGIN_SLUG}/"
echo ""
echo "Next steps:"
echo "1. Upload ${FINAL_ZIP} to WordPress.org plugin repository"
echo "2. The plugin will be extracted to wp-content/plugins/${PLUGIN_SLUG}/"
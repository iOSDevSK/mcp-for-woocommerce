#!/bin/bash

# Create release ZIP for WordPress upload
# Usage: ./create-release-zip.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get version from package.json
VERSION=$(grep '"version"' package.json | cut -d '"' -f 4)
PLUGIN_NAME="mcp-for-woocommerce"
ZIP_NAME="${PLUGIN_NAME}.zip"
TEMP_DIR="${PLUGIN_NAME}-tmp"

echo -e "${GREEN}Creating release ZIP for version ${VERSION}...${NC}"

# Clean up any existing temp directory
if [ -d "$TEMP_DIR" ]; then
    echo -e "${YELLOW}Cleaning up existing temp directory...${NC}"
    rm -rf "$TEMP_DIR"
fi

# Create temp directory
mkdir "$TEMP_DIR"

# Copy plugin files
echo -e "${GREEN}Copying plugin files...${NC}"
cp -r includes "$TEMP_DIR/"
cp -r vendor "$TEMP_DIR/"
cp -r build "$TEMP_DIR/"
cp mcp-for-woocommerce.php "$TEMP_DIR/"
cp changelog.txt "$TEMP_DIR/"
cp readme.txt "$TEMP_DIR/"
cp LICENSE "$TEMP_DIR/"
cp composer.json "$TEMP_DIR/"

# Copy client-setup.md if it exists
if [ -f "client-setup.md" ]; then
    cp client-setup.md "$TEMP_DIR/"
fi

# Remove unnecessary files
echo -e "${GREEN}Cleaning up unnecessary files...${NC}"
find "$TEMP_DIR" -name "*.sh" -delete
find "$TEMP_DIR" -name ".git*" -delete
find "$TEMP_DIR" -name ".wordpress-org" -type d -exec rm -rf {} + 2>/dev/null || true
find "$TEMP_DIR" -name "node_modules" -type d -exec rm -rf {} + 2>/dev/null || true
find "$TEMP_DIR" -name "*.log" -delete
find "$TEMP_DIR" -name "*.tmp" -delete
find "$TEMP_DIR" -name ".DS_Store" -delete

# Create ZIP
echo -e "${GREEN}Creating ZIP archive...${NC}"
cd "$TEMP_DIR"
zip -r "../$ZIP_NAME" *
cd ..

# Clean up temp directory
echo -e "${GREEN}Cleaning up...${NC}"
rm -rf "$TEMP_DIR"

# Verify ZIP was created
if [ -f "$ZIP_NAME" ]; then
    ZIP_SIZE=$(du -h "$ZIP_NAME" | cut -f1)
    echo -e "${GREEN}✅ ZIP package created successfully!${NC}"
    echo -e "${GREEN}   File: $ZIP_NAME${NC}"
    echo -e "${GREEN}   Size: $ZIP_SIZE${NC}"
    echo -e "${YELLOW}   Ready for WordPress upload${NC}"
else
    echo -e "${RED}❌ Failed to create ZIP package${NC}"
    exit 1
fi
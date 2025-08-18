# Claude Development Guidelines

## Pre-Push Testing Requirements

**CRITICAL:** Before every push to GitHub, execute all tests to ensure code stability and prevent critical errors.

### Required Test Commands

```bash
# Run PHP syntax check on all files
find includes -name "*.php" -exec php -l {} \;

# Run PHPUnit tests if available
vendor/bin/phpunit

# Check for WooCommerce plugin compatibility
# Ensure WooCommerce functions are properly available
```

### Testing Checklist

- [ ] PHP syntax validation passes
- [ ] No fatal errors in WordPress admin
- [ ] Plugin tools load correctly
- [ ] WooCommerce product tools function properly
- [ ] Product links are included in responses
- [ ] All callback methods work without REST API controller issues

### Common Issues to Test For

1. **REST API Controller Issues**: Avoid using `\WC_REST_Products_Controller()` in admin context
2. **Function Availability**: Ensure WooCommerce functions are available when tools load
3. **Admin Tool Loading**: Verify tools display properly in WordPress admin
4. **Product Link Generation**: Confirm `permalink` fields are included in product responses

## Post-Commit Synchronization Requirements

**CRITICAL:** After every commit, verify changes are synchronized across all environments.

### Required Sync Commands

```bash
# 1. Check local git status
git status
git log --oneline -1

# 2. Verify server sync
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && git status"
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && git log --oneline -1"

# 3. If server is behind, update server
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && git pull origin main"

# 4. Build on server after updates
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && npm run build"

# 5. Verify all environments have same commit hash
echo "Local:" && git log --oneline -1
echo "Server:" && ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && git log --oneline -1"
echo "GitHub: Check repository directly or use git ls-remote origin main"
```

### Synchronization Rules

1. **When making changes locally**: Always sync to Git from local, then update server
2. **When making changes on server**: Always sync to Git from server, then update local
3. **Always verify**: Ensure local, server, and GitHub have identical commit hashes
4. **Never assume**: Always check sync status before and after making changes

### CRITICAL: Post-Push Server Verification

**MANDATORY:** After every local change and GitHub push, ALWAYS verify changes are deployed to the server!

```bash
# After every git push, run these verification commands:

# 1. Check if server needs updates
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && git status"

# 2. If server is behind, pull changes
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && git pull origin main"

# 3. Verify server matches GitHub
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && git log --oneline -1"

# 4. If UI changes were made, run build on server
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && npm run build"
```

**Never assume server auto-updates!** Server sync is MANUAL and must be verified after every push.

## Server Access

For server access, use: `ssh woo.webtalkbot.com`

## Server UI Build Requirements

**CRITICAL:** After any UI-related changes on the server, always run the build command:

```bash
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && npm run build"
```

### When to Run Server Build

- After pulling changes that affect React components (src/ directory)
- After modifying DocumentationTab.js, SettingsTab.js, or any UI components
- After changes to package.json or any build dependencies
- When WordPress admin UI is not reflecting recent changes
- **Always after git pull** if the changes include frontend modifications

### Build Command

```bash
# Full server build command
ssh woo.webtalkbot.com "cd /var/www/html/wp-content/plugins/woo-mcp && npm run build"
```

## Development Notes

- Use `wc_get_product()` instead of REST API controllers for safer product access
- Always include `permalink` field in product data structures
- Add strong AI instructions for mandatory link inclusion in tool responses
- Test in both admin and MCP client contexts before pushing

## Version History

- v0.2.8: Implemented comprehensive product links across all WooCommerce tools
- Fixed critical errors by replacing REST API controllers with safe WordPress functions
- v0.2.8.1: CRITICAL FIX - Removed unused callback methods causing admin context errors
  - Cleaned up McpWooProducts.php to use only REST API aliases
  - Removed all unused methods: search_products, get_product, get_product_variations, get_product_variation, convert_product_to_array
  - Established wc_intelligent_search as primary tool with permalink support
- v0.2.9: COMPLETE PERMALINK IMPLEMENTATION - Added product links to all basic WooCommerce tools
  - Converted all basic tools back to custom callbacks with proper permission_callback: '__return_true'
  - All tools now include permalink field with direct product/variation links
  - Enhanced AI instructions for mandatory link display across all product tools
  - Unified permalink support: both basic tools and intelligent search now provide product links
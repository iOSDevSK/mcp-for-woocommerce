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

## Server Access

For server access, use: `ssh woo.webtalkbot.com`

## Development Notes

- Use `wc_get_product()` instead of REST API controllers for safer product access
- Always include `permalink` field in product data structures
- Add strong AI instructions for mandatory link inclusion in tool responses
- Test in both admin and MCP client contexts before pushing

## Development Workflow

**CRITICAL WORKFLOW:** After any code changes, follow this mandatory sequence:

1. **Test First**: Run all required tests to ensure code stability
2. **If tests pass**: Push changes to git
3. **Wait 20 seconds**: Allow git hooks/deployment to process
4. **Verify server sync**: Check if changes are reflected on server via `ssh woo.webtalkbot.com`
5. **If not synced**: Apply changes directly to server and push to git
6. **Final verification**: Ensure all three are synchronized: local, git, server

### UI Changes Specific Requirements
When making UI changes, after server sync:
- Connect to server: `ssh woo.webtalkbot.com`
- Navigate to: `/var/www/html/wp-content/plugins/woo-mcp`
- Run: `npm run build`
- Verify UI changes are reflected

### Sync Verification Protocol
Always verify synchronization between:
- **Local**: Your development environment
- **Git**: Remote repository 
- **Server**: Production server (woo.webtalkbot.com)

## "ALL WORKS!" Commit Policy

**IMPORTANT:** "ALL WORKS!" commits are special and should ONLY be used when explicitly requested by the user.

### When to Use "ALL WORKS!" Commits:
- **Only when user explicitly asks** for an "ALL WORKS!" commit
- **Only after user has tested** all changes on the server
- **Only when user confirms** everything is working correctly

### When NOT to Use "ALL WORKS!" Commits:
- **Never add "ALL WORKS!" automatically** without user request
- **Never assume** everything works without user testing
- **Never use** for regular commits, even if changes seem working

### User Testing Requirements:
Before creating "ALL WORKS!" commits, user must:
1. Test functionality on production server
2. Verify all UI changes work correctly
3. Confirm no breaking changes exist
4. Explicitly request the "ALL WORKS!" commit

**Remember**: "ALL WORKS!" commits are a confirmation from the user that they have personally tested and verified everything works on their server.

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
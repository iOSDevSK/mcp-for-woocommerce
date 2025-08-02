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

## Development Notes

- Use `wc_get_product()` instead of REST API controllers for safer product access
- Always include `permalink` field in product data structures
- Add strong AI instructions for mandatory link inclusion in tool responses
- Test in both admin and MCP client contexts before pushing

## Version History

- v0.2.8: Implemented comprehensive product links across all WooCommerce tools
- Fixed critical errors by replacing REST API controllers with safe WordPress functions
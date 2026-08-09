# Claude Development Guidelines

## Pre-Push Testing Requirements

**CRITICAL:** Before every push to GitHub, execute all tests to ensure code stability and prevent critical errors.

### Required Test Commands

```bash
# Run PHP syntax check on all files
find includes -name "*.php" -exec php -l {} \;

# Run PHPUnit tests if available.
# NOTE: not currently runnable — dev dependencies are not installed, there is no
# phpunit.xml, and the transport tests in tests/phpunit/ expect a WP_REST_Response
# from handlers that call exit(). Treat a missing run as a known gap, not a pass.
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

## Deployment

There is no staging/demo server for this plugin. The `woo.webtalkbot.com` host that
earlier revisions of this file described has been decommissioned — do not try to SSH
to it, sync it, or verify a push against it. GitHub is the only remote that matters.

Releases go out through `build-release.sh` / `create-release-zip.sh` and the
WordPress.org SVN repo; see those scripts before shipping a version.

## UI Build Requirements

After any change under `src/` (React admin components such as `DocumentationTab.js`
or `SettingsTab.js`), or any change to build dependencies in `package.json`, rebuild
before committing — the WordPress admin serves the compiled output from `build/`:

```bash
npm run build
```

## Local Testing Environment

`wp-env` is configured (`.wp-env.json`, port 8888). WooCommerce is a hard dependency,
so it must be activated before this plugin. A local `.wp-env.override.json` (untracked)
is the easiest way to add it:

```json
{
  "plugins": [
    "https://downloads.wordpress.org/plugin/woocommerce.zip",
    "https://downloads.wordpress.org/plugin/wordpress-importer.zip",
    "."
  ],
  "themes": ["https://downloads.wordpress.org/theme/storefront.zip"]
}
```

MCP is disabled until the settings option exists, and sample products give the
product tools something to return:

```bash
npx wp-env run cli -- wp option update mcpfowo_settings '{"enabled":1}' --format=json
npx wp-env run cli -- wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=create
```

**Transport testing caveat:** `wp-env` runs Apache + mod_php, which is more forgiving
about response headers than the nginx + PHP-FPM setup most users run. Bugs in the
`/wp-json/wp/v2/wpmcp/streamable` response path can be invisible on Apache and fatal
on nginx (see issue #5). Verify transport-level changes against nginx + PHP-FPM, and
inspect raw bytes with `curl --raw` rather than trusting a client that de-chunks for you.

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
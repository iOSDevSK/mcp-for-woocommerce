# GitHub Release Instructions for Woo-MCP Plugin

## Version 1.0.2-beta Release Guide

### Pre-Release Checklist

1. **Version Updated**: ✅ Version updated to 1.0.2-beta in `wordpress-mcp.php`
2. **Changelog Updated**: ✅ Updated `changelog.txt` with release notes
3. **Code Synchronized**: ✅ All changes pushed to GitHub repository
4. **ZIP Archive Created**: ✅ Archive `mcp-for-woocommerce-v1.0.2-beta.zip` (3.8MB) available

### Step-by-Step Release Process

#### 1. Navigate to GitHub Repository
- Go to: https://github.com/your-username/mcp-for-woocommerce
- Click on the **"Releases"** section in the right sidebar
- Or go directly to: https://github.com/your-username/mcp-for-woocommerce/releases

#### 2. Create New Release
- Click **"Create a new release"** button
- Or click **"Draft a new release"** if you see that option

#### 3. Configure Release Details

**Tag version:**
```
v1.0.2-beta
```

**Release title:**
```
Woo-MCP v1.0.2-beta - Critical PHP Error Fix & Tool Priority Reordering
```

**Description:**
```markdown
## 🚨 Critical Fixes and Tool Priority Reordering

### ⚡ Critical PHP Error Fix
- **FIXED**: Resolved PHP Fatal Error in WooCommerce product search tools
- **REPLACED**: Undefined `esc_like()` function with `sanitize_text_field()` 
- **RESOLVED**: "Internal error: Failed to execute tool" that was preventing product searches
- **TESTED**: German product search queries now work correctly (e.g., "Gläser unter 100 Euro")

### 🔄 MCP Tool Priority Optimization
- **PRIMARY**: `wc_products_search` is now the main product search tool (highest priority)
- **SECONDARY**: `wc_get_product` for detailed product information (medium priority)  
- **FALLBACK**: `wc_intelligent_search` for advanced search scenarios (lowest priority)
- **IMPROVED**: Tool descriptions and annotations guide proper usage sequence
- **OPTIMIZED**: Better search results by using basic tools first, advanced tools as fallback

### 🔗 Product Links Maintained
- **COMPLETE**: All tools continue to include product permalinks
- **MANDATORY**: AI instructions ensure product links are always displayed
- **UNIFIED**: Consistent permalink support across all WooCommerce tools

### 🛠️ Technical Details
- **Fixed File**: `includes/Tools/McpWooIntelligentSearch.php` line 736
- **Error Code**: `Call to undefined function Automattic\WordpressMcp\Tools\esc_like()`
- **Solution**: Used WordPress core sanitization function instead
- **Testing**: Verified on production server with real German search queries

### 📋 Installation Requirements
- WordPress 6.4+
- WooCommerce 3.0+
- PHP 8.0+

### 🔧 Upgrade Instructions
1. Deactivate the current plugin
2. Upload and extract the new ZIP file
3. Activate the plugin
4. Test product search functionality

---
**Full Changelog**: https://github.com/your-username/mcp-for-woocommerce/compare/v1.0.1-beta...v1.0.2-beta
```

#### 4. Upload ZIP File
- Scroll to **"Attach binaries by dropping them here or selecting them"**
- Upload the file: `mcp-for-woocommerce-v1.0.2-beta.zip`
- Wait for upload to complete

#### 5. Release Configuration
- **This is a pre-release**: ✅ **Check this box** (since it's a beta version)
- **Set as the latest release**: ✅ Check this if it's your newest version
- **Create a discussion for this release**: Optional (recommended for community feedback)

#### 6. Publish Release
- Click **"Publish release"** to make it live
- Or click **"Save draft"** if you want to review later

### Post-Release Actions

1. **Verify Release**: Check that the release appears in the releases list
2. **Test Download**: Download the ZIP file to verify it works
3. **Update Documentation**: Update any documentation that references version numbers
4. **Notify Users**: Consider notifying existing users about the critical fix

### Important Notes

- **Pre-release Status**: Mark as pre-release since this is a beta version
- **Critical Fix**: Emphasize this fixes a critical error that prevented product searches
- **Production Ready**: Despite beta status, this fixes critical functionality
- **Backward Compatible**: No breaking changes, safe to upgrade

### Troubleshooting

**If ZIP upload fails:**
- Check file size (should be ~3.8MB)
- Try refreshing the page and uploading again
- Ensure you're logged into GitHub

**If release creation fails:**
- Verify you have write permissions to the repository
- Check that the tag version doesn't already exist
- Ensure you're on the correct repository

### File Locations

- **ZIP Archive**: `/Users/filipdvoran/Developer/mcp-for-woocommerce-v1.0.2-beta.zip` (4.3MB)
- **Repository**: Local repository is synchronized with GitHub
- **Changelog**: Full details in `changelog.txt`

### ZIP Structure Note

✅ **Správna štruktúra pre WordPress**: ZIP súbor obsahuje súbory priamo v koreňovom adresári (nie v podpriečinku `mcp-for-woocommerce/`). Toto je správny formát pre WordPress pluginy.

Po nahratí do GitHub release a stiahnutí:
- WordPress administrátori môžu priamo nahrať ZIP súbor cez WordPress admin
- Po rozbalení vznikne priečinok `mcp-for-woocommerce/` v `wp-content/plugins/`
- Plugin bude pripravený na aktiváciu
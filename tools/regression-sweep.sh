#!/usr/bin/env bash
# Regression sweep for MCP for WooCommerce.
#
# Every finding a WordPress.org reviewer raised, as a grep with a hard expectation.
# A HIT is the bug. Run it on the tree a release is cut from (or the unpacked ZIP)
# before every version — updates reach users through SVN with no review at all.
#
# Usage: tools/regression-sweep.sh [plugin-dir]   (default: repo root)
set -uo pipefail

DIR="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
INC="$DIR/includes"
fail=0

# check <label> <grep -E pattern> [path...] — passes when NOTHING matches.
check() {
	local label="$1" pattern="$2"; shift 2
	local hits
	hits=$(grep -rnE --include='*.php' "$pattern" "${@:-$INC}" 2>/dev/null)
	if [ -n "$hits" ]; then
		echo "  ✗ $label"; echo "$hits" | sed 's/^/      /'; fail=1
	else
		echo "  ✓ $label"
	fi
}

# must <label> <grep -E pattern> <file> — passes when the pattern IS present.
must() {
	local label="$1" pattern="$2" file="$3"
	if grep -qE "$pattern" "$file" 2>/dev/null; then
		echo "  ✓ $label"
	else
		echo "  ✗ $label (missing in ${file#"$DIR"/})"; fail=1
	fi
}

echo "== Review AUTO-SVN 29Sep26 — remote administration / permission_callback"
check "no remote caller is ever made a WordPress user" "wp_set_current_user\("
check "no token / OAuth routes" "mcpfowo/v1|/auth/token|oauth-authorization-server|Firebase\\\\JWT"
check "no JWT-required switch left behind" "mcpfowo_jwt_required" "$INC"
must  "STDIO route is declared public with __return_true" "'permission_callback' => '__return_true'" "$INC/Core/McpStdioTransport.php"
must  "Streamable route is declared public with __return_true" "'permission_callback' => '__return_true'" "$INC/Core/McpStreamableTransport.php"
check "no custom permission callback that returns true for everyone" "function check_permission"
must  "admin-only tool listing checks manage_options" "current_user_can\( 'manage_options' \)" "$INC/RequestMethodHandlers/ToolsHandler.php"
check "no public OpenAPI document (it leaked admin_email)" "openapi_spec|admin_email"
check "no system status / server inventory tool" "wc_get_system_status|wc_get_system_tools"

echo "== Public data only"
check "posts/pages never take a status from the caller" "post_status' *=> *\\\$args"
check "no status filter in posts/pages input schema" "'enum' *=> *\['publish', *'draft'"
check "no reviewer / author e-mail or login in output" "comment_author_email|user_email|user_login" "$INC/Tools"
check "no gateway / shipping method settings in output" "get_form_fields\(|get_instance_form_fields\("
for f in McpWooProducts McpWooIntelligentSearch McpWooReviews McpWordPressPosts McpWordPressPages; do
	must "$f checks StorefrontVisibility" "StorefrontVisibility::is_(product|post)_public" "$INC/Tools/$f.php"
done

echo "== Guideline 5 — no locked or unregistered features"
check "registry accepts read tools only" "'type' *=> *'(create|update|delete|action)'"
check "no create/update/delete or CRUD switches" "enable_(create|update|delete)_tools|enable_rest_api_crud_tools|features_adapter_enabled"
check "no REST-alias tool mapping" "rest_alias"
for f in "$INC"/Tools/*.php; do
	cls=$(basename "$f" .php)
	if ! grep -qE "new $cls\(" "$INC/Core/WpMcp.php"; then
		echo "  ✗ $cls is shipped but never registered in WpMcp (locked feature)"; fail=1
	fi
done
echo "  ✓ every tool class is registered" | { [ $fail -eq 0 ] && cat || true; }

echo "== Writing to disallowed locations"
check "no file writes" "file_put_contents\(|fwrite\(|->put_contents\(|wp_mkdir_p\("
check "no executable proxy generator" "McpProxyGenerator|mcp-proxy\.js\" *\)|chmod\("
check "no SSL verification switched off" "'sslverify' *=> *false"

if [ $fail -ne 0 ]; then
	echo; echo "✗ a finding a reviewer already raised is back — fix before releasing"; exit 1
fi
echo; echo "✓ regression sweep clean"

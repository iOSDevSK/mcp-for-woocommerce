#!/usr/bin/env bash
# Streamable HTTP transport verification for mcp-for-woocommerce (issue #5)
# Usage: [BASE=http://host] ./streamable-transport.sh <label>
set -uo pipefail

BASE="${BASE:-http://localhost:8888}"
EP="$BASE/wp-json/wp/v2/wpmcp/streamable"
ACCEPT="application/json, text/event-stream"
LABEL="${1:-run}"
OUT="$(dirname "$0")/out-$LABEL"
mkdir -p "$OUT"

pass=0; fail=0
ok()   { echo "  PASS: $1"; pass=$((pass+1)); }
bad()  { echo "  FAIL: $1"; fail=$((fail+1)); }

echo "=== Getting JWT token ==="
TOKEN=$(curl -s -X POST "$BASE/wp-json/mcpfowo/v1/auth/token" \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"password"}' | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo $d["access_token"] ?? $d["token"] ?? "";')
if [ -z "$TOKEN" ]; then echo "!! no token obtained"; exit 1; fi
echo "token: ${TOKEN:0:25}..."

# $1 = name, $2 = json body
post_raw() {
  local name="$1" body="$2"
  curl -s --raw -D "$OUT/$name.headers" -o "$OUT/$name.body" \
    -X POST "$EP" \
    -H "Authorization: Bearer $TOKEN" \
    -H 'Content-Type: application/json' \
    -H "Accept: $ACCEPT" \
    --data "$body"
}

check_clean_json() {
  local name="$1"
  echo "--- $name ---"
  echo "  first 32 bytes:"; head -c 32 "$OUT/$name.body" | xxd | sed 's/^/    /'
  grep -i '^transfer-encoding' "$OUT/$name.headers" >/dev/null \
    && bad "$name: response carries Transfer-Encoding header" \
    || ok "$name: no Transfer-Encoding header"
  if php -r 'exit(json_decode(file_get_contents($argv[1]))===null && trim(file_get_contents($argv[1]))!=="null" ? 1 : 0);' "$OUT/$name.body"; then
    ok "$name: body is valid JSON"
  else
    bad "$name: body is NOT valid JSON"
    echo "    body head: $(head -c 120 "$OUT/$name.body" | tr -d '\0' | cat -v)"
  fi
}

echo
echo "=== 1. initialize ==="
post_raw initialize '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"issue5-test","version":"1.0"}}}'
check_clean_json initialize
grep -i '^mcp-session-id' "$OUT/initialize.headers" >/dev/null && ok "initialize: Mcp-Session-Id header present" || bad "initialize: Mcp-Session-Id header MISSING"

echo
echo "=== 2. tools/list ==="
post_raw toolslist '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'
check_clean_json toolslist
php -r '$d=json_decode(file_get_contents($argv[1]),true); $n=count($d["result"]["tools"]??[]); echo "  tools returned: $n\n"; exit($n>0?0:1);' "$OUT/toolslist.body" \
  && ok "tools/list: tools present" || bad "tools/list: no tools"

echo
echo "=== 3. tools/call - product search (sample products) ==="
post_raw toolscall '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"wc_products_search","arguments":{"search":"beanie","per_page":5}}}'
check_clean_json toolscall
echo "  result excerpt: $(head -c 300 "$OUT/toolscall.body")"

echo
echo "=== 4. batch of 6 requests (exercises large-batch path) ==="
post_raw batch '[{"jsonrpc":"2.0","id":11,"method":"ping"},{"jsonrpc":"2.0","id":12,"method":"ping"},{"jsonrpc":"2.0","id":13,"method":"ping"},{"jsonrpc":"2.0","id":14,"method":"ping"},{"jsonrpc":"2.0","id":15,"method":"ping"},{"jsonrpc":"2.0","id":16,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"t","version":"1"}}}]'
check_clean_json batch
php -r '$d=json_decode(file_get_contents($argv[1]),true); $n=is_array($d)?count($d):0; echo "  batch items: $n\n"; exit($n===6?0:1);' "$OUT/batch.body" \
  && ok "batch: 6 items returned" || bad "batch: wrong item count"
grep -i '^mcp-session-id' "$OUT/batch.headers" >/dev/null && ok "batch: Mcp-Session-Id header present" || bad "batch: Mcp-Session-Id header MISSING"

echo
echo "=== 5. small batch of 2 ==="
post_raw batch2 '[{"jsonrpc":"2.0","id":21,"method":"ping"},{"jsonrpc":"2.0","id":22,"method":"ping"}]'
check_clean_json batch2

echo
echo "=== 6. notification only -> 202, empty body ==="
post_raw notif '{"jsonrpc":"2.0","method":"notifications/initialized"}'
head -1 "$OUT/notif.headers"
grep -q '202' <(head -1 "$OUT/notif.headers") && ok "notification: HTTP 202" || bad "notification: not 202"
[ ! -s "$OUT/notif.body" ] && ok "notification: empty body" || bad "notification: body not empty ($(head -c 40 "$OUT/notif.body" | cat -v))"

echo
echo "=== 7. GET health ==="
curl -s --raw -D "$OUT/health.headers" -o "$OUT/health.body" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: $ACCEPT" "$EP"
check_clean_json health

echo
echo "=== 8. HEAD ==="
curl -s --raw -D "$OUT/head.headers" -o /dev/null -I \
  -H "Authorization: Bearer $TOKEN" -H "Accept: $ACCEPT" "$EP"
head -1 "$OUT/head.headers"
grep -q '200' <(head -1 "$OUT/head.headers") && ok "HEAD: HTTP 200" || bad "HEAD: not 200"
grep -i '^transfer-encoding' "$OUT/head.headers" >/dev/null \
  && bad "HEAD: response carries Transfer-Encoding header" \
  || ok "HEAD: no Transfer-Encoding header"

echo
echo "=== 9. error path: bad Accept header ==="
curl -s --raw -D "$OUT/badaccept.headers" -o "$OUT/badaccept.body" \
  -X POST "$EP" -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' -H 'Accept: text/plain' \
  --data '{"jsonrpc":"2.0","id":9,"method":"ping"}'
check_clean_json badaccept

echo
echo "================ $LABEL: $pass passed, $fail failed ================"
exit $(( fail > 0 ? 1 : 0 ))

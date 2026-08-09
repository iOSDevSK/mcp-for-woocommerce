#!/usr/bin/env bash
# Streamable transport verification for the JWT-disabled proxy mode
# (handle_streamable_proxy_mode) - a separate emitter path from the JWT path.
# Usage: [BASE=http://host] [CLI=<wp-cli container>] ./streamable-proxy-mode.sh <label>
set -uo pipefail

BASE="${BASE:-http://localhost:8888}"
EP="$BASE/wp-json/wp/v2/wpmcp/streamable"
ACCEPT="application/json, text/event-stream"
CLI="${CLI:-wp-env-mcp-for-woocommerce-93c628a2-cli-1}"
LABEL="${1:-proxy}"
OUT="$(dirname "$0")/out-$LABEL"
mkdir -p "$OUT"

pass=0; fail=0
ok()  { echo "  PASS: $1"; pass=$((pass+1)); }
bad() { echo "  FAIL: $1"; fail=$((fail+1)); }

cleanup() { docker exec -u 33 "$CLI" wp option update mcpfowo_jwt_required 1 >/dev/null 2>&1; }
trap cleanup EXIT

echo "=== disabling JWT requirement (proxy mode) ==="
docker exec -u 33 "$CLI" wp option update mcpfowo_jwt_required 0 | tail -1

post_raw() {
  curl -s --raw -D "$OUT/$1.headers" -o "$OUT/$1.body" \
    -X POST "$EP" -H 'Content-Type: application/json' -H "Accept: $ACCEPT" --data "$2"
}

check_clean_json() {
  local name="$1"
  echo "--- $name ---"
  echo "  first 32 bytes:"; head -c 32 "$OUT/$name.body" | xxd | sed 's/^/    /'
  grep -i '^transfer-encoding' "$OUT/$name.headers" >/dev/null \
    && bad "$name: response carries Transfer-Encoding header" \
    || ok "$name: no Transfer-Encoding header"
  if php -r 'exit(json_decode(file_get_contents($argv[1]))===null ? 1 : 0);' "$OUT/$name.body"; then
    ok "$name: body is valid JSON"
  else
    bad "$name: body is NOT valid JSON"
    echo "    body head: $(head -c 120 "$OUT/$name.body" | cat -v)"
  fi
}

echo
echo "=== proxy mode: initialize (no Authorization header) ==="
post_raw p_init '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"issue5","version":"1"}}}'
check_clean_json p_init

echo
echo "=== proxy mode: tools/list ==="
post_raw p_tools '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'
check_clean_json p_tools
php -r '$d=json_decode(file_get_contents($argv[1]),true); $n=count($d["result"]["tools"]??[]); echo "  tools returned: $n\n"; exit($n>0?0:1);' "$OUT/p_tools.body" \
  && ok "proxy tools/list: tools present" || bad "proxy tools/list: no tools"

echo
echo "=== proxy mode: tools/call product search ==="
post_raw p_call '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"wc_products_search","arguments":{"search":"hoodie","per_page":3}}}'
check_clean_json p_call
php -r '$d=json_decode(file_get_contents($argv[1]),true); $t=$d["result"]["content"][0]["text"]??""; $p=json_decode($t,true); $n=count($p["products"]??[]); echo "  products: $n\n"; foreach(($p["products"]??[]) as $x){echo "   - {$x["name"]} :: {$x["permalink"]}\n";} exit($n>0?0:1);' "$OUT/p_call.body" \
  && ok "proxy tools/call: products returned" || bad "proxy tools/call: no products"

echo
echo "=== proxy mode: unknown method (error path) ==="
post_raw p_err '{"jsonrpc":"2.0","id":4,"method":"does/not/exist","params":{}}'
check_clean_json p_err

echo
echo "================ $LABEL: $pass passed, $fail failed ================"
exit $(( fail > 0 ? 1 : 0 ))

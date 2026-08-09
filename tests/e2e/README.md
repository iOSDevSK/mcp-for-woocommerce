# Streamable HTTP transport — end-to-end tests

Transport-level regression tests for `/wp-json/wp/v2/wpmcp/streamable`. They exercise
the **wire format**, which PHPUnit cannot: the response emitter calls `exit()`, so the
only honest way to check what a client actually receives is to make real HTTP requests
and look at the raw bytes.

These exist because of [issue #5](https://github.com/iOSDevSK/mcp-for-woocommerce/issues/5),
where the transport wrote HTTP chunked framing into the response body. **Apache + mod_php
hid the bug** — it honours the application-set `Transfer-Encoding` header, so a
de-chunking client saw clean JSON. nginx + PHP-FPM re-frames the body, and every
JSON-RPC response arrived corrupted. Run the nginx stack before shipping any change to
the response path.

## Setup

### Apache (wp-env, port 8888)

Create an untracked `.wp-env.override.json` in the repo root — WooCommerce must
activate **before** this plugin, so list it first:

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

```bash
npx wp-env start
npx wp-env run cli -- wp theme activate storefront
npx wp-env run cli -- wp option update mcpfowo_settings '{"enabled":1}' --format=json
npx wp-env run cli -- wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=create
```

### nginx + PHP-FPM (port 8899)

```bash
cd tests/e2e/nginx-stack
echo "PLUGIN_DIR=$(cd ../../.. && pwd)" > .env
docker compose up -d

docker compose exec -T cli wp core install --url=http://localhost:8899 \
  --title="MCP Woo e2e" --admin_user=admin --admin_password=password \
  --admin_email=admin@example.org --skip-email
docker compose exec -T cli wp rewrite structure '/%postname%/'
docker compose exec -T cli wp plugin install woocommerce wordpress-importer --activate
docker compose exec -T cli wp theme install storefront --activate
docker compose exec -T cli wp plugin activate mcp-for-woocommerce
docker compose exec -T cli wp option update mcpfowo_settings '{"enabled":1}' --format=json
docker compose exec -T cli wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=create
```

## Running

Both stacks assume `admin` / `password`.

```bash
# JWT-required path (the default)
BASE=http://localhost:8899 tests/e2e/streamable-transport.sh nginx
tests/e2e/streamable-transport.sh apache            # defaults to :8888

# JWT-disabled proxy path — a separate emitter, toggles the option and restores it
BASE=http://localhost:8899 CLI=nginx-stack-cli-1 tests/e2e/streamable-proxy-mode.sh nginx

# End-to-end with the official MCP SDK client
npm install --no-save @modelcontextprotocol/sdk
BASE=http://localhost:8899 node tests/e2e/sdk-client.mjs
```

Each script prints per-check PASS/FAIL, dumps the first 32 bytes of every response
body, and exits non-zero on any failure. Response artifacts land in `out-<label>/`
next to the scripts (untracked).

**Gotcha:** the plugin caps a user at 10 active JWTs, and every run mints one. Once
you hit the cap the scripts abort with `!! no token obtained`. Clear it with:

```bash
npx wp-env run cli -- wp option delete mcpfowo_jwt_token_registry
# nginx stack: docker compose exec -T cli wp option delete mcpfowo_jwt_token_registry
```

## What is covered

`initialize`, `tools/list`, `tools/call` (product search against the sample data),
2- and 6-message batches, notification-only `202`, `GET` health, `HEAD`, and the
error paths — each asserting the body is valid JSON and that no `Transfer-Encoding`
header originates from the application.

The 6-message batch matters: it used to take a separate "large batch" code path that
emitted the `Mcp-Session-Id` header after body output, silently dropping it.

## Teardown

```bash
npx wp-env destroy
cd tests/e2e/nginx-stack && docker compose down -v
```

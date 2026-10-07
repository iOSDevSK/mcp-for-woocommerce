// End-to-end check with the official MCP SDK Streamable HTTP client.
const BASE = process.env.BASE || 'http://localhost:8888';
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StreamableHTTPClientTransport } from '@modelcontextprotocol/sdk/client/streamableHttp.js';

const url = new URL(`${BASE}/wp-json/wp/v2/wpmcp/streamable`);

// The endpoint is public by design: the client sends no credentials.
const transport = new StreamableHTTPClientTransport(url);

const client = new Client({ name: 'issue5-e2e', version: '1.0.0' }, { capabilities: {} });

await client.connect(transport);
console.log('connect(): OK — initialize handshake completed');
console.log('server:', JSON.stringify(client.getServerVersion()));

const { tools } = await client.listTools();
console.log(`tools/list: ${tools.length} tools`);

const call = await client.callTool({
	name: 'wc_products_search',
	arguments: { search: 'cap', per_page: 3 },
});
const payload = JSON.parse(call.content[0].text);
console.log(`tools/call wc_products_search: ${payload.products.length} products`);
for (const p of payload.products) console.log(`  - ${p.name} :: ${p.permalink}`);

await client.close();
console.log('\nE2E via official MCP SDK Streamable HTTP client: PASS');

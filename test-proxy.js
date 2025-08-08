#!/usr/bin/env node

import { spawn } from 'child_process';

// Test the proxy with a simple initialize request
const proxy = spawn('node', ['/Users/filipdvoran/Downloads/woo-mcp-proxy.js']);

proxy.stdout.on('data', (data) => {
    console.log('Response:', data.toString());
    proxy.kill();
});

proxy.stderr.on('data', (data) => {
    console.log('Error log:', data.toString());
});

// Send initialize request
const initRequest = {
    jsonrpc: '2.0',
    id: 1,
    method: 'initialize',
    params: {
        protocolVersion: '2024-11-05',
        capabilities: {},
        clientInfo: {
            name: 'test-client',
            version: '1.0.0'
        }
    }
};

setTimeout(() => {
    proxy.stdin.write(JSON.stringify(initRequest) + '\n');
}, 100);

// Timeout after 5 seconds
setTimeout(() => {
    console.log('Test completed');
    proxy.kill();
    process.exit(0);
}, 5000);
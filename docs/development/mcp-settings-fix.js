// MCP Settings Fix - manually create mcpSettings object
console.log('Manually creating mcpSettings object...');

window.mcpSettings = {
    ajaxUrl: '/wp-admin/admin-ajax.php',
    nonce: 'manual-nonce',
    enabledTools: [],
    availableTools: [],
    mcpEnabled: true,
    jwtRequired: "0", // This will trigger Claude connector UI
    featureApiAvailable: false,
    pluginUrl: 'https://woo.webtalkbot.com/wp-content/plugins/woo-mcp/',
    claudeSetupInstructions: {
        proxyPath: '/var/www/html/wp-content/plugins/woo-mcp/mcp-proxy.js',
        config: '{\n  "mcpServers": {\n    "woocommerce": {\n      "command": "node",\n      "args": ["/var/www/html/wp-content/plugins/woo-mcp/mcp-proxy.js"]\n    }\n  }\n}'
    },
    strings: {
        enableMcp: 'Enable MCP functionality',
        enableMcpDescription: 'Toggle to enable or disable the MCP plugin functionality.',
        featureApiDescription: 'Enable the use of WordPress Features API.',
        tools: 'Tools',
        toolsDescription: 'Select the tools you want to enable for the MCP plugin.',
        authenticationTokens: 'Authentication Tokens',
        authenticationTokensDescription: 'Manage your JWT tokens for API access.',
        documentation: 'Documentation',
        documentationDescription: 'Read the plugin documentation and setup guides.',
        resources: 'Resources',
        resourcesDescription: 'View available MCP resources.',
        prompts: 'Prompts',
        promptsDescription: 'View available MCP prompts.',
        settings: 'Settings',
        settingsDescription: 'Configure the MCP plugin settings.',
        saveSettings: 'Save Settings',
        settingsSaved: 'Settings saved successfully!',
        settingsError: 'Error saving settings. Please try again.',
        tokenGenerated: 'Token generated successfully!',
        tokenError: 'Error generating token. Please try again.',
        tokenRevoked: 'Token revoked successfully!',
        tokenRevokeError: 'Error revoking token. Please try again.',
        generateToken: 'Generate Token',
        revokeToken: 'Revoke Token',
        tokenName: 'Token Name',
        tokenNamePlaceholder: 'Enter token name',
        activeTokens: 'Active Tokens',
        noActiveTokens: 'No active tokens found.',
        tokenCreatedAt: 'Created',
        tokenLastUsed: 'Last Used',
        tokenActions: 'Actions',
        confirmRevoke: 'Are you sure you want to revoke this token?',
        securityWarning: 'Security Warning',
        neverExpiringTokens: 'Never-Expiring Tokens:',
        requireJwtAuth: 'Require JWT Authentication',
        requireJwtAuthDescription: 'When enabled, all MCP requests must include a valid JWT token. When disabled, MCP endpoints are accessible without authentication (readonly mode only) and can be used as a Claude.ai Desktop connector.',
        webtalkbotNote: 'Note for Webtalkbot users:',
        webtalkbotDescription: 'JWT Authentication is recommended if you want to create a WooCommerce AI Agent in',
        claudeConnectorNote: 'Claude.ai Desktop Connector:',
        claudeConnectorDescription: 'When JWT Authentication is disabled, this plugin can be used as a connector in Claude.ai Desktop. A proxy file will be automatically generated for easy setup.',
        proxyFileGenerated: 'MCP Proxy file generated at:',
        claudeSetupInstructions: 'To use with Claude.ai Desktop, add this configuration to your claude_desktop_config.json:'
    }
};

console.log('mcpSettings created:', window.mcpSettings);
console.log('jwtRequired:', window.mcpSettings.jwtRequired);
console.log('claudeSetupInstructions:', window.mcpSettings.claudeSetupInstructions);

// Force React component re-render if possible
if (window.React && window.ReactDOM) {
    console.log('Attempting to trigger React re-render...');
    setTimeout(() => {
        const event = new Event('mcpSettingsUpdated');
        document.dispatchEvent(event);
    }, 1000);
}
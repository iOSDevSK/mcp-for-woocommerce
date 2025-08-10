/**
 * WordPress dependencies
 */
import {
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	ToggleControl,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Settings Tab Component
 */
const SettingsTab = ( { settings, onToggleChange, isSaving, strings } ) => {

	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'General Settings', 'wordpress-mcp' ) }</h2>
			</CardHeader>
			<CardBody>
				<div className="setting-row">
					<ToggleControl
						label={
							strings.enableMcp ||
							__( 'Enable MCP functionality', 'wordpress-mcp' )
						}
						help={
							strings.enableMcpDescription ||
							__(
								'Toggle to enable or disable the MCP plugin functionality.',
								'wordpress-mcp'
							)
						}
						checked={ settings.enabled }
						onChange={ () => onToggleChange( 'enabled' ) }
					/>
				</div>

			</CardBody>
		</Card>
	);
};

const AuthenticationCard = ( { jwtRequired, onJwtRequiredToggle, isSaving, strings } ) => {
	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Authentication Settings', 'wordpress-mcp' ) }</h2>
			</CardHeader>
			<CardBody>
				{/* Webtalkbot Information - always visible at top */}
				<div style={{ marginBottom: '20px', padding: '12px', backgroundColor: '#f0f6fc', border: '1px solid #d1ecf1', borderRadius: '4px' }}>
					<p style={{ margin: '0', fontSize: '14px', color: '#0c5460' }}>
						<strong>{ strings.webtalkbotNote || __( 'Note for Webtalkbot users:', 'wordpress-mcp' ) }</strong> { strings.webtalkbotDescription || __( 'JWT Authentication must be enabled if you want to create a WooCommerce AI Agent in', 'wordpress-mcp' ) }{' '}
						<a 
							href="https://webtalkbot.com" 
							target="_blank" 
							rel="noopener noreferrer"
							style={{ color: '#0c5460', textDecoration: 'underline' }}
						>
							Webtalkbot
						</a>.
					</p>
				</div>

				<div className="setting-row">
					<ToggleControl
						label={
							strings.requireJwtAuth ||
							__( 'Require JWT Authentication', 'wordpress-mcp' )
						}
						help={
							strings.requireJwtAuthDescription ||
							__(
								'When enabled, all MCP requests must include a valid JWT token. When disabled, MCP endpoints are accessible without authentication (readonly mode only).',
								'wordpress-mcp'
							)
						}
						checked={ jwtRequired }
						onChange={ onJwtRequiredToggle }
					/>

					{/* Claude.ai Desktop Connector Information */}
					{(!jwtRequired || jwtRequired === '0' || jwtRequired === 0) && window.wordpressMcpSettings.claudeSetupInstructions && (
						<div className="notice notice-info inline" style={{ marginTop: '15px', padding: '12px' }}>
							<h4 style={{ margin: '0 0 8px 0' }}>{ strings.claudeConnectorNote || __( 'Claude.ai Desktop Connector:', 'wordpress-mcp' ) }</h4>
							<p style={{ margin: '0 0 8px 0' }}>{ strings.claudeConnectorDescription || __( 'When JWT Authentication is disabled, this plugin can be used as a connector in Claude.ai Desktop. A proxy file will be automatically generated for easy setup.', 'wordpress-mcp' ) }</p>
							
							<p style={{ margin: '8px 0', fontWeight: 'bold' }}>{ strings.proxyFilesGenerated || __( 'MCP Proxy files generated at:', 'wordpress-mcp' ) }</p>
							
							<p style={{ margin: '4px 0', fontSize: '14px', fontWeight: 'bold', color: '#0073aa' }}>{ __( 'PHP Proxy:', 'wordpress-mcp' ) }</p>
							<code style={{ 
								display: 'block', 
								padding: '8px', 
								backgroundColor: '#f0f0f0', 
								border: '1px solid #ddd',
								marginBottom: '8px',
								fontSize: '12px'
							}}>
								{window.wordpressMcpSettings.claudeSetupInstructions.phpProxyPath || window.wordpressMcpSettings.claudeSetupInstructions.proxyPath.replace('.js', '.php')}
							</code>
							
							<p style={{ margin: '4px 0', fontSize: '14px', fontWeight: 'bold', color: '#0073aa' }}>{ __( 'Node.js Proxy:', 'wordpress-mcp' ) }</p>
							<code style={{ 
								display: 'block', 
								padding: '8px', 
								backgroundColor: '#f0f0f0', 
								border: '1px solid #ddd',
								marginBottom: '8px',
								fontSize: '12px'
							}}>
								{window.wordpressMcpSettings.claudeSetupInstructions.proxyPath}
							</code>
							
							<p style={{ margin: '8px 0', fontWeight: 'bold' }}>{ strings.claudeSetupInstructions || __( 'To use with Claude.ai Desktop, add this configuration to your claude_desktop_config.json:', 'wordpress-mcp' ) }</p>
							
							<p style={{ margin: '8px 0', fontWeight: 'bold', color: '#0073aa' }}>{ __( 'PHP Version (requires PHP installed):', 'wordpress-mcp' ) }</p>
							<pre style={{ 
								backgroundColor: '#f0f0f0', 
								border: '1px solid #ddd',
								padding: '12px',
								fontSize: '12px',
								overflowX: 'auto',
								margin: '8px 0'
							}}>
{window.wordpressMcpSettings.claudeSetupInstructions.phpConfig || window.wordpressMcpSettings.claudeSetupInstructions.config}
							</pre>

							<p style={{ margin: '8px 0', fontWeight: 'bold', color: '#0073aa' }}>{ __( 'Node.js Version (requires Node.js installed):', 'wordpress-mcp' ) }</p>
							<pre style={{ 
								backgroundColor: '#f0f0f0', 
								border: '1px solid #ddd',
								padding: '12px',
								fontSize: '12px',
								overflowX: 'auto',
								margin: '8px 0'
							}}>
{window.wordpressMcpSettings.claudeSetupInstructions.nodeConfig || window.wordpressMcpSettings.claudeSetupInstructions.config}
							</pre>
						</div>
					)}

				</div>
			</CardBody>
			{ isSaving && (
				<CardFooter>
					<div className="settings-saving-indicator">
						<Spinner />
						{ __( 'Saving...', 'wordpress-mcp' ) }
					</div>
				</CardFooter>
			) }
		</Card>
	);
};

export { AuthenticationCard };
export default SettingsTab;

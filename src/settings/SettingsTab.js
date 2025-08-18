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
const SettingsTab = ( { settings, onToggleChange, isSaving, strings, systemStatus } ) => {

	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'General Settings', 'wordpress-mcp' ) }</h2>
			</CardHeader>
			<CardBody>
				{/* System Requirements Warnings - only show if there are issues */}
				{systemStatus && (!systemStatus.restApiEnabled || !systemStatus.permalinksCorrect) && (
					<div style={{ marginBottom: '24px' }}>
						{/* WordPress REST API Warning - only if disabled */}
						{!systemStatus.restApiEnabled && (
							<div style={{ marginBottom: '16px', padding: '16px', backgroundColor: '#fff3cd', border: '1px solid #ffeaa7', borderRadius: '4px' }}>
								<h4 style={{ margin: '0 0 8px 0', color: '#856404', fontSize: '14px', fontWeight: 'bold' }}>
									{ __( '⚠️ WordPress REST API Disabled', 'wordpress-mcp' ) }
								</h4>
								<p style={{ margin: '0', fontSize: '14px', color: '#856404', lineHeight: '1.4' }}>
									{ __( 'WordPress REST API appears to be disabled. This plugin requires REST API to function properly. Please contact your administrator to ensure the WordPress REST API is enabled.', 'wordpress-mcp' ) }
								</p>
							</div>
						)}

						{/* Permalinks Warning - only if not set to Post name */}
						{!systemStatus.permalinksCorrect && (
							<div style={{ marginBottom: '16px', padding: '16px', backgroundColor: '#fff3cd', border: '1px solid #ffeaa7', borderRadius: '4px' }}>
								<h4 style={{ margin: '0 0 8px 0', color: '#856404', fontSize: '14px', fontWeight: 'bold' }}>
									{ __( '⚠️ Incorrect Permalinks Configuration', 'wordpress-mcp' ) }
								</h4>
								<p style={{ margin: '0', fontSize: '14px', color: '#856404', lineHeight: '1.4' }}>
									{ __( 'WordPress permalinks are not set to "Post name" structure. For proper product link generation, please go to WordPress Admin → Settings → Permalinks and select "Post name" structure.', 'wordpress-mcp' ) }
								</p>
							</div>
						)}
					</div>
				)}
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
						<strong>{ strings.webtalkbotNote || __( 'Note for Webtalkbot users:', 'wordpress-mcp' ) }</strong> { strings.webtalkbotDescription || __( 'JWT Authentication is recommended if you want to create a WooCommerce AI Agent in', 'wordpress-mcp' ) }{' '}
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

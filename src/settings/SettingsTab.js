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
				<h2>{ __( 'General Settings', 'mcp-for-woocommerce' ) }</h2>
			</CardHeader>
			<CardBody>
				{/* System Requirements Warnings - only show if there are issues */}
				{systemStatus && (!systemStatus.restApiEnabled || !systemStatus.permalinksCorrect) && (
					<div style={{ marginBottom: '24px' }}>
						{/* WordPress REST API Warning - only if disabled */}
						{!systemStatus.restApiEnabled && (
							<div style={{ marginBottom: '16px', padding: '16px', backgroundColor: '#fff3cd', border: '1px solid #ffeaa7', borderRadius: '4px' }}>
								<h4 style={{ margin: '0 0 8px 0', color: '#856404', fontSize: '14px', fontWeight: 'bold' }}>
									{ __( '⚠️ WordPress REST API Disabled', 'mcp-for-woocommerce' ) }
								</h4>
								<p style={{ margin: '0', fontSize: '14px', color: '#856404', lineHeight: '1.4' }}>
									{ __( 'WordPress REST API appears to be disabled. This plugin requires REST API to function properly. Please contact your administrator to ensure the WordPress REST API is enabled.', 'mcp-for-woocommerce' ) }
								</p>
							</div>
						)}

						{/* Permalinks Warning - only if not set to Post name */}
						{!systemStatus.permalinksCorrect && (
							<div style={{ marginBottom: '16px', padding: '16px', backgroundColor: '#fff3cd', border: '1px solid #ffeaa7', borderRadius: '4px' }}>
								<h4 style={{ margin: '0 0 8px 0', color: '#856404', fontSize: '14px', fontWeight: 'bold' }}>
									{ __( '⚠️ Incorrect Permalinks Configuration', 'mcp-for-woocommerce' ) }
								</h4>
								<p style={{ margin: '0', fontSize: '14px', color: '#856404', lineHeight: '1.4' }}>
									{ __( 'WordPress permalinks are not set to "Post name" structure. For proper product link generation, please go to WordPress Admin → Settings → Permalinks and select "Post name" structure.', 'mcp-for-woocommerce' ) }
								</p>
							</div>
						)}
					</div>
				)}
				<div className="setting-row">
					<ToggleControl
						label={
							strings.enableMcp ||
							__( 'Enable MCP functionality', 'mcp-for-woocommerce' )
						}
						help={
							strings.enableMcpDescription ||
							__(
								'Toggle to enable or disable the MCP plugin functionality.',
								'mcp-for-woocommerce'
							)
						}
						checked={ settings.enabled }
						onChange={ () => onToggleChange( 'enabled' ) }
					/>
				</div>

			</CardBody>
			{ isSaving && (
				<CardFooter>
					<div className="settings-saving-indicator">
						<Spinner />
						{ __( 'Saving...', 'mcp-for-woocommerce' ) }
					</div>
				</CardFooter>
			) }
		</Card>
	);
};

const AccessCard = ( { strings } ) => {
	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Access', 'mcp-for-woocommerce' ) }</h2>
			</CardHeader>
			<CardBody>
				<p style={{ margin: '0 0 12px 0' }}>
					{ strings.publicAccessNote ||
						__(
							'The MCP endpoint is public. It returns only information that shop visitors can already see, and it cannot create, change or delete anything.',
							'mcp-for-woocommerce'
						) }
				</p>
				<p style={{ margin: '0' }}>
					{ __( 'MCP endpoint:', 'mcp-for-woocommerce' ) }{ ' ' }
					<code>{ window.mcpfowoSettings?.mcpEndpoint }</code>
				</p>
			</CardBody>
		</Card>
	);
};

export { AccessCard };
export default SettingsTab;

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

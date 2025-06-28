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
import { createInterpolateElement } from '@wordpress/element';
import { useEffect } from '@wordpress/element';

/**
 * Settings Tab Component
 */
const SettingsTab = ( { settings, onToggleChange, isSaving, strings } ) => {
	// Check if WordPress Features API is available
	// When passing from PHP to JS via wp_localize_script, booleans become strings
	const isFeatureApiAvailable =
		window.wordpressMcpSettings?.featureApiAvailable === true ||
		window.wordpressMcpSettings?.featureApiAvailable === '1';

	// Add debugging - can be removed after fixing the issue
	useEffect( () => {}, [ isFeatureApiAvailable ] );

	// Determine if the feature toggle should be disabled
	const isFeatureToggleDisabled =
		! settings.enabled || ! isFeatureApiAvailable;

	// Create the help text with link for Feature API
	const featureApiHelpText = isFeatureApiAvailable
		? strings.enableFeaturesAdapterDescription ||
		  __(
				'Enable or disable the WordPress Features Adapter. This option only works when MCP is enabled.',
				'wordpress-mcp'
		  )
		: createInterpolateElement(
				__(
					'WordPress Feature API is not available. Please <a>install</a> and activate the WordPress Feature API plugin.',
					'wordpress-mcp'
				),
				{
					a: (
						<a
							href="https://github.com/Automattic/wp-feature-api"
							target="_blank"
							rel="noopener noreferrer"
						/>
					),
				}
		  );

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

				<div className="setting-row">
					<ToggleControl
						label={
							strings.enableFeaturesAdapter ||
							__(
								'Enable WordPress Features Adapter',
								'wordpress-mcp'
							)
						}
						help={ featureApiHelpText }
						checked={
							isFeatureApiAvailable
								? settings.features_adapter_enabled
								: false
						}
						onChange={ () => {
							if ( isFeatureApiAvailable && settings.enabled ) {
								onToggleChange( 'features_adapter_enabled' );
							}
						} }
						disabled={ isFeatureToggleDisabled }
					/>
				</div>

				<div className="setting-row">
					<ToggleControl
						label={
							strings.enableCreateTools ||
							__( 'Enable Create Tools', 'wordpress-mcp' )
						}
						help={
							strings.enableCreateToolsDescription ||
							__(
								'Allow create operations via tools.',
								'wordpress-mcp'
							)
						}
						checked={ settings.enable_create_tools }
						onChange={ () =>
							onToggleChange( 'enable_create_tools' )
						}
						disabled={ ! settings.enabled }
					/>
				</div>
				<div className="setting-row">
					<ToggleControl
						label={
							strings.enableUpdateTools ||
							__( 'Enable Update Tools', 'wordpress-mcp' )
						}
						help={
							strings.enableUpdateToolsDescription ||
							__(
								'Allow update operations via tools.',
								'wordpress-mcp'
							)
						}
						checked={ settings.enable_update_tools }
						onChange={ () =>
							onToggleChange( 'enable_update_tools' )
						}
						disabled={ ! settings.enabled }
					/>
				</div>
				<div className="setting-row">
					<ToggleControl
						label={
							strings.enableDeleteTools ||
							__( 'Enable Delete Tools', 'wordpress-mcp' )
						}
						help={
							strings.enableDeleteToolsDescription ||
							__(
								'⚠️ CAUTION: Allow deletion operations via tools.',
								'wordpress-mcp'
							)
						}
						checked={ settings.enable_delete_tools }
						onChange={ () =>
							onToggleChange( 'enable_delete_tools' )
						}
						disabled={ ! settings.enabled }
					/>
				</div>
				<div className="setting-row">
					<ToggleControl
						label={
							strings.enableRestApiCrudTools ||
							__(
								'🧪 Enable REST API CRUD Tools (EXPERIMENTAL)',
								'wordpress-mcp'
							)
						}
						help={
							strings.enableRestApiCrudToolsDescription ||
							__(
								'⚠️ EXPERIMENTAL FEATURE: Enable or disable the generic REST API CRUD tools for accessing WordPress endpoints. This is experimental functionality that may change or be removed in future versions. When enabled, all tools that are a rest_alias or have the disabled_by_rest_crud flag will be disabled.',
								'wordpress-mcp'
							)
						}
						checked={ settings.enable_rest_api_crud_tools }
						onChange={ () =>
							onToggleChange( 'enable_rest_api_crud_tools' )
						}
						disabled={ ! settings.enabled }
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

export default SettingsTab;

/**
 * WordPress dependencies
 */
import { useState, useEffect, useRef, useMemo } from '@wordpress/element';
import { Notice, TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Import the extracted components
import SettingsTab, { AccessCard } from './SettingsTab.js';
import ToolsTab from './ToolsTab.js';
import ResourcesTab from './ResourcesTab.js';
import PromptsTab from './PromptsTab.js';
import DocumentationTab from './DocumentationTab.js';

/**
 * Settings App Component
 */
export const SettingsApp = () => {
	// Get initial tab from URL hash
	const getInitialTab = () => {
		const hash = window.location.hash.replace( '#', '' );
		return hash || 'settings';
	};

	// State for settings
	const [ settings, setSettings ] = useState( {
		enabled: false,
	} );

	// State for UI
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const [ activeTab, setActiveTab ] = useState( getInitialTab() );

	// Ref for tracking pending save timeouts
	const saveTimeoutRef = useRef( null );

	// Define tabs with useMemo to prevent unnecessary re-renders
	const tabs = useMemo(
		() => [
			{
				name: 'settings',
				title: __( 'Settings', 'mcp-for-woocommerce' ),
				className: 'mcpfowo-settings-tab',
			},
			{
				name: 'documentation',
				title: __( 'Documentation', 'mcp-for-woocommerce' ),
				className: 'mcpfowo-documentation-tab',
			},
			{
				name: 'tools',
				title: __( 'Tools', 'mcp-for-woocommerce' ),
				className: 'mcpfowo-tools-tab',
				disabled: ! settings.enabled,
			},
			{
				name: 'resources',
				title: __( 'Resources', 'mcp-for-woocommerce' ),
				className: 'mcpfowo-resources-tab',
				disabled: ! settings.enabled,
			},
			{
				name: 'prompts',
				title: __( 'Prompts', 'mcp-for-woocommerce' ),
				className: 'mcpfowo-prompts-tab',
				disabled: ! settings.enabled,
			},
		],
		[ settings.enabled ]
	);

	// Load settings
	useEffect( () => {
		if (
			window.mcpfowoSettings &&
			window.mcpfowoSettings.settings
		) {
			const loaded = window.mcpfowoSettings.settings;
			setSettings( ( prev ) => ( {
				...prev,
				enabled: loaded.enabled || false,
			} ) );
		}
	}, [] );

	// Handle tab selection
	const handleTabSelect = ( tabName ) => {
		const tab = tabs.find( ( t ) => t.name === tabName );
		if ( ! tab.disabled ) {
			setActiveTab( tabName );
			window.location.hash = tabName;
			return tabName;
		}
		return activeTab;
	};

	// Clean up any pending timeouts on unmounting
	useEffect( () => {
		return () => {
			if ( saveTimeoutRef.current ) {
				clearTimeout( saveTimeoutRef.current );
			}
		};
	}, [] );

	// Handle toggle changes
	const handleToggleChange = ( key ) => {
		const newValue = ! settings[ key ];

		// Update settings state with the new value
		setSettings( ( prevSettings ) => {
			const updatedSettings = {
				...prevSettings,
				[ key ]: newValue,
			};

			// If disabling MCP and currently on a restricted tab, switch to settings tab
			if ( key === 'enabled' && ! newValue && activeTab !== 'settings' ) {
				setActiveTab( 'settings' );
				window.location.hash = 'settings';
			}

			// Clear any pending save timeout
			if ( saveTimeoutRef.current ) {
				clearTimeout( saveTimeoutRef.current );
			}

			// Automatically save settings after state is updated
			saveTimeoutRef.current = setTimeout( () => {
				handleSaveSettingsWithData( updatedSettings );
				saveTimeoutRef.current = null;
			}, 500 );

			return updatedSettings;
		} );
	};

	// Save settings with specific data
	const handleSaveSettingsWithData = ( settingsData ) => {
		setIsSaving( true );
		setNotice( null );

		// Create form data for AJAX request
		const formData = new FormData();
		formData.append( 'action', 'mcpfowo_save_settings' );
		formData.append( 'nonce', window.mcpfowoSettings.nonce );
		formData.append( 'settings', JSON.stringify( settingsData ) );

		// Send AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				setIsSaving( false );
				if ( data.success ) {
					setNotice( {
						status: 'success',
						message:
							data.data.message ||
							window.mcpfowoSettings.strings.settingsSaved,
					} );
				} else {
					setNotice( {
						status: 'error',
						message:
							data.data.message ||
							window.mcpfowoSettings.strings.settingsError,
					} );
				}
			} )
			.catch( ( error ) => {
				setIsSaving( false );
				setNotice( {
					status: 'error',
					message: window.mcpfowoSettings.strings.settingsError,
				} );
				console.error( 'Error saving settings:', error );
			} );
	};

	// Handle save settings button click
	const handleSaveSettings = () => {
		handleSaveSettingsWithData( settings );
	};

	// Get localized strings
	const strings = window.mcpfowoSettings
		? window.mcpfowoSettings.strings
		: {};

	// Get system status
	const systemStatus = window.mcpfowoSettings
		? window.mcpfowoSettings.systemStatus
		: null;

	return (
		<div className="mcpfowo-settings">
			{ notice && (
				<Notice
					status={ notice.status }
					isDismissible={ true }
					onRemove={ () => setNotice( null ) }
					className={ `notice notice-${ notice.status } is-dismissible` }
				>
					{ notice.message }
				</Notice>
			) }

			<TabPanel
				className="mcpfowo-tabs"
				tabs={ tabs }
				activeClass="is-active"
				initialTabName={ activeTab }
				onSelect={ handleTabSelect }
			>
				{ ( tab ) => {
					if ( tab.disabled ) {
						const disabledMessage = __(
							'This feature is only available when MCP functionality is enabled.',
							'mcp-for-woocommerce'
						);
						const enableMessage = __(
							'Please enable MCP in the Settings tab first.',
							'mcp-for-woocommerce'
						);

						return (
							<div className="mcpfowo-disabled-tab-notice">
								<p>{ disabledMessage }</p>
								<p>{ enableMessage }</p>
							</div>
						);
					}

					switch ( tab.name ) {
						case 'settings':
							return (
								<>
									<SettingsTab
										settings={ settings }
										onToggleChange={ handleToggleChange }
										isSaving={ isSaving }
										strings={ strings }
										systemStatus={ systemStatus }
									/>
									<br />
									<AccessCard strings={ strings } />
								</>
							);
						case 'documentation':
							return <DocumentationTab />;
						case 'tools':
							return <ToolsTab />;
						case 'resources':
							return <ResourcesTab />;
						case 'prompts':
							return <PromptsTab />;
						default:
							return null;
					}
				} }
			</TabPanel>
		</div>
	);
};

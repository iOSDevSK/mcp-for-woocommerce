/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './settings/style.css';
import { SettingsApp } from './settings';

// Initialize the app when the DOM is ready
document.addEventListener( 'DOMContentLoaded', function () {
	const container = document.getElementById( 'wordpress-mcp-settings-app' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <SettingsApp /> );
	}
} );

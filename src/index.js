/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './settings/style.css';
import { SettingsApp } from './settings/index.js';

// Initialize the app when the DOM is ready
document.addEventListener( 'DOMContentLoaded', function () {
	const container = document.getElementById( 'mcpfowo-settings-app' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <SettingsApp /> );
	}
} );

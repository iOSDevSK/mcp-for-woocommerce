/**
 * WordPress dependencies
 */
import {
	Card,
	CardHeader,
	CardBody,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Tools Tab Component
 */
const ToolsTab = () => {
	const [ tools, setTools ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	useEffect( () => {
		const fetchTools = async () => {
			try {
				setLoading( true );
				const response = await apiFetch( {
					path: '/wp/v2/wpmcp',
					method: 'POST',
					data: {
						jsonrpc: '2.0',
						method: 'tools/list/all',
						params: {},
					},
				} );

				if ( response && response.tools ) {
					setTools( response.tools );
				} else {
					setError(
						__( 'Failed to load tools data', 'wordpress-mcp' )
					);
				}
			} catch ( err ) {
				setError(
					__( 'Error loading tools: ', 'wordpress-mcp' ) + err.message
				);
			} finally {
				setLoading( false );
			}
		};

		fetchTools();
	}, [] );

	const handleToggleChange = async ( toolName, newState ) => {
		try {
			setSaving( true );
			// Update local state immediately for better UX
			setTools( ( prevTools ) =>
				prevTools.map( ( tool ) =>
					tool.name === toolName
						? { ...tool, tool_enabled: newState }
						: tool
				)
			);

			// Create form data for AJAX request
			const formData = new FormData();
			formData.append( 'action', 'wordpress_mcp_toggle_tool' );
			formData.append( 'nonce', window.wordpressMcpSettings.nonce );
			formData.append( 'tool', toolName );
			formData.append( 'tool_enabled', newState );

			// Send AJAX request
			const response = await fetch( ajaxurl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			} );

			const data = await response.json();

			if ( ! data.success ) {
				throw new Error(
					data.data.message ||
						window.wordpressMcpSettings.strings.settingsError
				);
			}

			// Show success message
			setError( null );
		} catch ( err ) {
			// Revert the state if the save fails
			setTools( ( prevTools ) =>
				prevTools.map( ( tool ) =>
					tool.name === toolName
						? { ...tool, tool_enabled: ! newState }
						: tool
				)
			);
			setError(
				err.message || window.wordpressMcpSettings.strings.settingsError
			);
			console.error( 'Error saving tool state:', err );
		} finally {
			setSaving( false );
		}
	};

	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Registered Tools', 'wordpress-mcp' ) }</h2>
			</CardHeader>
			<CardBody>
				<p>
					{ __(
						'List of all registered tools in the system. Use the toggles to enable or disable individual tools.',
						'wordpress-mcp'
					) }
				</p>

				{ loading ? (
					<div className="wordpress-mcp-loading">
						<Spinner />
						<p>{ __( 'Loading tools...', 'wordpress-mcp' ) }</p>
					</div>
				) : error ? (
					<div className="wordpress-mcp-error">
						<p>{ error }</p>
					</div>
				) : tools.length === 0 ? (
					<p>
						{ __(
							'No tools are currently registered.',
							'wordpress-mcp'
						) }
					</p>
				) : (
					<table className="wordpress-mcp-table">
						<thead>
							<tr>
								<th>{ __( 'Name', 'wordpress-mcp' ) }</th>
								<th>
									{ __( 'Description', 'wordpress-mcp' ) }
								</th>
								<th>
									{ __(
										'Functionality Type',
										'wordpress-mcp'
									) }
								</th>
								<th>{ __( 'Status', 'wordpress-mcp' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ tools.map( ( tool ) => (
								<tr key={ tool.name }>
									<td>
										<strong>{ tool.name }</strong>
									</td>
									<td>{ tool.description }</td>
									<td>{ tool.type }</td>
									<td>
										<ToggleControl
											checked={
												tool.tool_enabled &&
												tool.tool_type_enabled
											}
											onChange={ ( value ) =>
												handleToggleChange(
													tool.name,
													value
												)
											}
											disabled={
												saving ||
												! tool.tool_type_enabled
											}
											label={
												tool.tool_enabled &&
												tool.tool_type_enabled
													? __(
															'Enabled',
															'wordpress-mcp'
													  )
													: __(
															'Disabled',
															'wordpress-mcp'
													  )
											}
										/>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</CardBody>
		</Card>
	);
};

export default ToolsTab;

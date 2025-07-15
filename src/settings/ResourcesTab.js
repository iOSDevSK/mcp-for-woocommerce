/**
 * WordPress dependencies
 */
import {
	Card,
	CardHeader,
	CardBody,
	Spinner,
	Button,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Pretty prints JSON data
 *
 * @param {Object} data The data to pretty print
 * @return {string} Formatted JSON string
 */
const prettyPrintJson = ( data ) => {
	try {
		// If data is a string, try to parse it as JSON
		if ( typeof data === 'string' ) {
			try {
				const parsedData = JSON.parse( data );
				return JSON.stringify( parsedData, null, 2 );
			} catch ( parseError ) {
				// If parsing fails, return the original string
				return data;
			}
		}

		// If data is an object with a text property that looks like JSON
		if (
			data &&
			typeof data === 'object' &&
			data.text &&
			typeof data.text === 'string'
		) {
			try {
				const parsedText = JSON.parse( data.text );
				return JSON.stringify( parsedText, null, 2 );
			} catch ( parseError ) {
				// If parsing fails, continue with normal formatting
			}
		}

		// Default case: format the object directly
		return JSON.stringify( data, null, 2 );
	} catch ( error ) {
		return 'Error formatting JSON: ' + error.message;
	}
};

/**
 * Resources Tab Component
 */
const ResourcesTab = () => {
	const [ resources, setResources ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ selectedResource, setSelectedResource ] = useState( null );
	const [ resourceDetails, setResourceDetails ] = useState( null );
	const [ detailsLoading, setDetailsLoading ] = useState( false );
	const [ detailsError, setDetailsError ] = useState( null );
	const [ parsedJsonData, setParsedJsonData ] = useState( null );

	useEffect( () => {
		const fetchResources = async () => {
			try {
				setLoading( true );
				const response = await apiFetch( {
					path: '/wp/v2/wpmcp',
					method: 'POST',
					data: {
						jsonrpc: '2.0',
						method: 'resources/list',
						params: {},
					},
				} );

				if ( response && response.resources ) {
					setResources( response.resources );
				} else {
					setError(
						__( 'Failed to load resources data', 'wordpress-mcp' )
					);
				}
			} catch ( err ) {
				setError(
					__( 'Error loading resources: ', 'wordpress-mcp' ) +
						err.message
				);
			} finally {
				setLoading( false );
			}
		};

		fetchResources();
	}, [] );

	/**
	 * Fetch detailed information about a resource
	 *
	 * @param {Object} resource The resource to fetch details for
	 */
	const fetchResourceDetails = async ( resource ) => {
		try {
			setDetailsLoading( true );
			setDetailsError( null );
			setParsedJsonData( null );

			const response = await apiFetch( {
				path: '/wp/v2/wpmcp',
				method: 'POST',
				data: {
					jsonrpc: '2.0',
					method: 'resources/read',
					uri: resource.uri,
				},
			} );

			if ( response && response.contents ) {
				setResourceDetails( response.contents );

				// Try to parse JSON text if it exists
				if (
					response.contents.text &&
					typeof response.contents.text === 'string'
				) {
					try {
						const parsedData = JSON.parse( response.contents.text );
						setParsedJsonData( parsedData );
					} catch ( parseError ) {
						// If parsing fails, leave parsedJsonData as null
						console.log( 'Failed to parse JSON text:', parseError );
					}
				}
			} else {
				setDetailsError(
					__( 'Failed to load resource details', 'wordpress-mcp' )
				);
			}
		} catch ( err ) {
			setDetailsError(
				__( 'Error loading resource details: ', 'wordpress-mcp' ) +
					err.message
			);
		} finally {
			setDetailsLoading( false );
		}
	};

	/**
	 * Handle viewing a resource
	 *
	 * @param {Object} resource The resource to view
	 */
	const viewResource = ( resource ) => {
		setSelectedResource( resource );
		fetchResourceDetails( resource );
	};

	/**
	 * Close the resource details modal
	 */
	const closeModal = () => {
		setSelectedResource( null );
		setResourceDetails( null );
		setParsedJsonData( null );
		setDetailsError( null );
	};

	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Available Resources', 'wordpress-mcp' ) }</h2>
			</CardHeader>
			<CardBody>
				<p>
					{ __(
						'List of all available resources in the system.',
						'wordpress-mcp'
					) }
				</p>

				{ loading ? (
					<div className="wordpress-mcp-loading">
						<Spinner />
						<p>{ __( 'Loading resources...', 'wordpress-mcp' ) }</p>
					</div>
				) : error ? (
					<div className="wordpress-mcp-error">
						<p>{ error }</p>
					</div>
				) : resources.length === 0 ? (
					<p>
						{ __(
							'No resources are currently available.',
							'wordpress-mcp'
						) }
					</p>
				) : (
					<table className="wordpress-mcp-table">
						<thead>
							<tr>
								<th>{ __( 'Name', 'wordpress-mcp' ) }</th>
								<th>{ __( 'URI', 'wordpress-mcp' ) }</th>
								<th>
									{ __( 'Description', 'wordpress-mcp' ) }
								</th>
								<th>{ __( 'Actions', 'wordpress-mcp' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ resources.map( ( resource ) => (
								<tr key={ resource.name }>
									<td>
										<strong>{ resource.name }</strong>
									</td>
									<td>{ resource.uri }</td>
									<td>{ resource.description || '-' }</td>
									<td>
										<Button
											variant="secondary"
											onClick={ () =>
												viewResource( resource )
											}
										>
											{ __( 'View', 'wordpress-mcp' ) }
										</Button>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }

				{ selectedResource && (
					<Modal
						title={ __( 'Resource Details', 'wordpress-mcp' ) }
						onRequestClose={ closeModal }
						className="wordpress-mcp-resource-modal"
					>
						{ detailsLoading ? (
							<div className="wordpress-mcp-loading">
								<Spinner />
								<p>
									{ __(
										'Loading resource details...',
										'wordpress-mcp'
									) }
								</p>
							</div>
						) : detailsError ? (
							<div className="wordpress-mcp-error">
								<p>{ detailsError }</p>
							</div>
						) : resourceDetails ? (
							<div className="wordpress-mcp-resource-details">
								<h3>
									{ resourceDetails.name ||
										selectedResource.name }
								</h3>

								<div className="wordpress-mcp-resource-json">
									<h4>
										{ __(
											'Full Resource Data',
											'wordpress-mcp'
										) }
									</h4>
									<pre className="wordpress-mcp-json-display">
										{ prettyPrintJson( resourceDetails ) }
									</pre>
								</div>
							</div>
						) : (
							<p>
								{ __(
									'No details available for this resource.',
									'wordpress-mcp'
								) }
							</p>
						) }
					</Modal>
				) }
			</CardBody>
		</Card>
	);
};

export default ResourcesTab;

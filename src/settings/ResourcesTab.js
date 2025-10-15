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
						__( 'Failed to load resources data', 'mcp-for-woocommerce' )
					);
				}
			} catch ( err ) {
				setError(
					__( 'Error loading resources: ', 'mcp-for-woocommerce' ) +
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
					__( 'Failed to load resource details', 'mcp-for-woocommerce' )
				);
			}
		} catch ( err ) {
			setDetailsError(
				__( 'Error loading resource details: ', 'mcp-for-woocommerce' ) +
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
				<h2>{ __( 'Available Resources', 'mcp-for-woocommerce' ) }</h2>
			</CardHeader>
			<CardBody>
				<p>
					{ __(
						'List of all available resources in the system.',
						'mcp-for-woocommerce'
					) }
				</p>

				{ loading ? (
					<div className="mcpfowo-loading">
						<Spinner />
						<p>{ __( 'Loading resources...', 'mcp-for-woocommerce' ) }</p>
					</div>
				) : error ? (
					<div className="mcpfowo-error">
						<p>{ error }</p>
					</div>
				) : resources.length === 0 ? (
					<p>
						{ __(
							'No resources are currently available.',
							'mcp-for-woocommerce'
						) }
					</p>
				) : (
					<table className="mcpfowo-table">
						<thead>
							<tr>
								<th>{ __( 'Name', 'mcp-for-woocommerce' ) }</th>
								<th>{ __( 'URI', 'mcp-for-woocommerce' ) }</th>
								<th>
									{ __( 'Description', 'mcp-for-woocommerce' ) }
								</th>
								<th>{ __( 'Actions', 'mcp-for-woocommerce' ) }</th>
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
											{ __( 'View', 'mcp-for-woocommerce' ) }
										</Button>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }

				{ selectedResource && (
					<Modal
						title={ __( 'Resource Details', 'mcp-for-woocommerce' ) }
						onRequestClose={ closeModal }
						className="mcpfowo-resource-modal"
					>
						{ detailsLoading ? (
							<div className="mcpfowo-loading">
								<Spinner />
								<p>
									{ __(
										'Loading resource details...',
										'mcp-for-woocommerce'
									) }
								</p>
							</div>
						) : detailsError ? (
							<div className="mcpfowo-error">
								<p>{ detailsError }</p>
							</div>
						) : resourceDetails ? (
							<div className="mcpfowo-resource-details">
								<h3>
									{ resourceDetails.name ||
										selectedResource.name }
								</h3>

								<div className="mcpfowo-resource-json">
									<h4>
										{ __(
											'Full Resource Data',
											'mcp-for-woocommerce'
										) }
									</h4>
									<pre className="mcpfowo-json-display">
										{ prettyPrintJson( resourceDetails ) }
									</pre>
								</div>
							</div>
						) : (
							<p>
								{ __(
									'No details available for this resource.',
									'mcp-for-woocommerce'
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

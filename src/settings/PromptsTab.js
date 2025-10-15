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
 * Prompts Tab Component
 */
const PromptsTab = () => {
	const [ prompts, setPrompts ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ selectedPrompt, setSelectedPrompt ] = useState( null );
	const [ showPromptDetails, setShowPromptDetails ] = useState( false );
	const [ loadingDetails, setLoadingDetails ] = useState( false );
	const [ detailsError, setDetailsError ] = useState( null );

	useEffect( () => {
		const fetchPrompts = async () => {
			try {
				setLoading( true );
				const response = await apiFetch( {
					path: '/wp/v2/wpmcp',
					method: 'POST',
					data: {
						jsonrpc: '2.0',
						method: 'prompts/list',
						params: {},
					},
				} );

				if ( response && response.prompts ) {
					setPrompts( response.prompts );
				} else {
					setError(
						__( 'Failed to load prompts data', 'mcp-for-woocommerce' )
					);
				}
			} catch ( err ) {
				setError(
					__( 'Error loading prompts: ', 'mcp-for-woocommerce' ) +
						err.message
				);
			} finally {
				setLoading( false );
			}
		};

		fetchPrompts();
	}, [] );

	const handleViewPrompt = async ( prompt ) => {
		try {
			setLoadingDetails( true );
			setDetailsError( null );

			const response = await apiFetch( {
				path: '/wp/v2/wpmcp',
				method: 'POST',
				data: {
					jsonrpc: '2.0',
					method: 'prompts/get',
					name: prompt.name,
				},
			} );

			if ( response && ( response.description || response.messages ) ) {
				const promptData = {
					name: prompt.name,
					description: response.description || '',
					content:
						response.messages && response.messages.length > 0
							? response.messages[ 0 ].content.text || ''
							: '',
					parameters: response.parameters || {},
				};

				setSelectedPrompt( promptData );
				setShowPromptDetails( true );
				console.log( 'Setting showPromptDetails to true', promptData );
			} else {
				setDetailsError(
					__( 'Failed to load prompt details', 'mcp-for-woocommerce' )
				);
			}
		} catch ( err ) {
			setDetailsError(
				__( 'Error loading prompt details: ', 'mcp-for-woocommerce' ) +
					err.message
			);
		} finally {
			setLoadingDetails( false );
		}
	};

	const handleClosePromptDetails = () => {
		setShowPromptDetails( false );
		setSelectedPrompt( null );
		setDetailsError( null );
	};

	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Available Prompts', 'mcp-for-woocommerce' ) }</h2>
			</CardHeader>
			<CardBody>
				<p>
					{ __(
						'List of all available prompts in the system.',
						'mcp-for-woocommerce'
					) }
				</p>

				{ loading ? (
					<div className="mcpfowo-loading">
						<Spinner />
						<p>{ __( 'Loading prompts...', 'mcp-for-woocommerce' ) }</p>
					</div>
				) : error ? (
					<div className="mcpfowo-error">
						<p>{ error }</p>
					</div>
				) : prompts.length === 0 ? (
					<p>
						{ __(
							'No prompts are currently available.',
							'mcp-for-woocommerce'
						) }
					</p>
				) : (
					<table className="mcpfowo-table">
						<thead>
							<tr>
								<th>{ __( 'Name', 'mcp-for-woocommerce' ) }</th>
								<th>
									{ __( 'Description', 'mcp-for-woocommerce' ) }
								</th>
								<th>{ __( 'Actions', 'mcp-for-woocommerce' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ prompts.map( ( prompt ) => (
								<tr key={ prompt.id }>
									<td>
										<strong>{ prompt.name }</strong>
									</td>
									<td>{ prompt.description || '-' }</td>
									<td>
										<Button
											variant="secondary"
											onClick={ () =>
												handleViewPrompt( prompt )
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

				{ showPromptDetails && (
					<Modal
						title={
							selectedPrompt
								? selectedPrompt.name
								: __( 'Prompt Details', 'mcp-for-woocommerce' )
						}
						onRequestClose={ handleClosePromptDetails }
						className="mcpfowo-prompt-modal"
					>
						{ loadingDetails ? (
							<div className="mcpfowo-loading">
								<Spinner />
								<p>
									{ __(
										'Loading prompt details...',
										'mcp-for-woocommerce'
									) }
								</p>
							</div>
						) : detailsError ? (
							<div className="mcpfowo-error">
								<p>{ detailsError }</p>
							</div>
						) : selectedPrompt ? (
							<div className="mcpfowo-prompt-details-content">
								<p>
									<strong>
										{ __(
											'Description:',
											'mcp-for-woocommerce'
										) }
									</strong>{ ' ' }
									{ selectedPrompt.description ||
										__(
											'No description available',
											'mcp-for-woocommerce'
										) }
								</p>
								{ selectedPrompt.content && (
									<div className="mcpfowo-prompt-content">
										<strong>
											{ __(
												'Content:',
												'mcp-for-woocommerce'
											) }
										</strong>
										<div>{ selectedPrompt.content }</div>
									</div>
								) }
								{ selectedPrompt.parameters && (
									<div className="mcpfowo-prompt-parameters">
										<strong>
											{ __(
												'Parameters:',
												'mcp-for-woocommerce'
											) }
										</strong>
										<pre>
											{ JSON.stringify(
												selectedPrompt.parameters,
												null,
												2
											) }
										</pre>
									</div>
								) }
							</div>
						) : null }
						<div className="mcpfowo-modal-footer">
							<Button
								variant="primary"
								onClick={ handleClosePromptDetails }
							>
								{ __( 'Close', 'mcp-for-woocommerce' ) }
							</Button>
						</div>
					</Modal>
				) }
			</CardBody>
		</Card>
	);
};

export default PromptsTab;

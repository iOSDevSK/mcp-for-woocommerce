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
						__( 'Failed to load prompts data', 'wordpress-mcp' )
					);
				}
			} catch ( err ) {
				setError(
					__( 'Error loading prompts: ', 'wordpress-mcp' ) +
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
					__( 'Failed to load prompt details', 'wordpress-mcp' )
				);
			}
		} catch ( err ) {
			setDetailsError(
				__( 'Error loading prompt details: ', 'wordpress-mcp' ) +
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
				<h2>{ __( 'Available Prompts', 'wordpress-mcp' ) }</h2>
			</CardHeader>
			<CardBody>
				<p>
					{ __(
						'List of all available prompts in the system.',
						'wordpress-mcp'
					) }
				</p>

				{ loading ? (
					<div className="wordpress-mcp-loading">
						<Spinner />
						<p>{ __( 'Loading prompts...', 'wordpress-mcp' ) }</p>
					</div>
				) : error ? (
					<div className="wordpress-mcp-error">
						<p>{ error }</p>
					</div>
				) : prompts.length === 0 ? (
					<p>
						{ __(
							'No prompts are currently available.',
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
								<th>{ __( 'Actions', 'wordpress-mcp' ) }</th>
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
											{ __( 'View', 'wordpress-mcp' ) }
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
								: __( 'Prompt Details', 'wordpress-mcp' )
						}
						onRequestClose={ handleClosePromptDetails }
						className="wordpress-mcp-prompt-modal"
					>
						{ loadingDetails ? (
							<div className="wordpress-mcp-loading">
								<Spinner />
								<p>
									{ __(
										'Loading prompt details...',
										'wordpress-mcp'
									) }
								</p>
							</div>
						) : detailsError ? (
							<div className="wordpress-mcp-error">
								<p>{ detailsError }</p>
							</div>
						) : selectedPrompt ? (
							<div className="wordpress-mcp-prompt-details-content">
								<p>
									<strong>
										{ __(
											'Description:',
											'wordpress-mcp'
										) }
									</strong>{ ' ' }
									{ selectedPrompt.description ||
										__(
											'No description available',
											'wordpress-mcp'
										) }
								</p>
								{ selectedPrompt.content && (
									<div className="wordpress-mcp-prompt-content">
										<strong>
											{ __(
												'Content:',
												'wordpress-mcp'
											) }
										</strong>
										<div>{ selectedPrompt.content }</div>
									</div>
								) }
								{ selectedPrompt.parameters && (
									<div className="wordpress-mcp-prompt-parameters">
										<strong>
											{ __(
												'Parameters:',
												'wordpress-mcp'
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
						<div className="wordpress-mcp-modal-footer">
							<Button
								variant="primary"
								onClick={ handleClosePromptDetails }
							>
								{ __( 'Close', 'wordpress-mcp' ) }
							</Button>
						</div>
					</Modal>
				) }
			</CardBody>
		</Card>
	);
};

export default PromptsTab;

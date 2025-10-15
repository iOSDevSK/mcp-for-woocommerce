// src/settings/AuthenticationTokensTab.js

import React, { useState, useEffect } from 'react';
import {
	Card,
	CardBody,
	Button,
	TextareaControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { format } from 'date-fns';

const AuthenticationTokensTab = () => {
	const [ token, setToken ] = useState( null );
	const [ tokens, setTokens ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ copySuccess, setCopySuccess ] = useState( false );
	const [ selectedDuration, setSelectedDuration ] = useState( 3600 ); // Default to 1 hour

	// UPDATED: Duration options včetně "never"
	const durationOptions = [
		{ label: __( '1 hour', 'mcp-for-woocommerce' ), value: 3600 },
		{ label: __( '2 hours', 'mcp-for-woocommerce' ), value: 7200 },
		{ label: __( '6 hours', 'mcp-for-woocommerce' ), value: 21600 },
		{ label: __( '12 hours', 'mcp-for-woocommerce' ), value: 43200 },
		{ label: __( '24 hours (1 day)', 'mcp-for-woocommerce' ), value: 86400 },
		{ label: __( 'Never expire', 'mcp-for-woocommerce' ), value: 'never' },
	];

	useEffect( () => {
		// Fetch tokens when component mounts
		fetchTokens();
	}, [] );

	// Clear copy success message after 2 seconds
	useEffect( () => {
		if ( copySuccess ) {
			const timer = setTimeout( () => {
				setCopySuccess( false );
			}, 2000 );
			return () => clearTimeout( timer );
		}
	}, [ copySuccess ] );

	const fetchTokens = async () => {
		try {
			// Try with fallback URL if wp-json doesn't work
			const jwtApiUrl = window.mcpfowoSettings?.jwtApiUrl || '/jwt-auth/v1';
			const fallbackUrl = window.mcpfowoSettings?.restFallbackUrl;
			
			let response;
			try {
				response = await apiFetch( {
					path: '/jwt-auth/v1/tokens',
					method: 'GET',
					includeCredentials: true,
				} );
			} catch (firstError) {
				// If primary path fails, try fallback
				if (fallbackUrl) {
					response = await apiFetch( {
						url: fallbackUrl + '/jwt-auth/v1/tokens',
						method: 'GET',
						includeCredentials: true,
					} );
				} else {
					throw firstError;
				}
			}
			
			setTokens( response );
			setError( null ); // Clear any previous errors
		} catch ( err ) {
			console.error( 'Token fetch error:', err );
			
			// Show more detailed error message based on the error
			if ( err.code === 'rest_forbidden' || err.status === 401 ) {
				setError( 
					err.message || __( 'You need to be logged in as an administrator to access JWT tokens.', 'mcp-for-woocommerce' )
				);
			} else if ( err.status === 403 ) {
				setError( __( 'You do not have permission to access JWT tokens. Admin privileges required.', 'mcp-for-woocommerce' ) );
			} else {
				setError( 
					err.message || __( 'Error fetching tokens', 'mcp-for-woocommerce' )
				);
			}
		}
	};

	// UPDATED: Generate token s podporou "never"
	const generateToken = async () => {
		setLoading( true );
		setError( null );

		try {
			const fallbackUrl = window.mcpfowoSettings?.restFallbackUrl;
			
			let response;
			try {
				response = await apiFetch( {
					path: '/jwt-auth/v1/token',
					method: 'POST',
					data: {
						expires_in: selectedDuration,
					},
					includeCredentials: true,
				} );
			} catch (firstError) {
				// If primary path fails, try fallback
				if (fallbackUrl) {
					response = await apiFetch( {
						url: fallbackUrl + '/jwt-auth/v1/token',
						method: 'POST',
						data: {
							expires_in: selectedDuration,
						},
						includeCredentials: true,
					} );
				} else {
					throw firstError;
				}
			}

			setToken( response );
			// Refresh the tokens list
			fetchTokens();
		} catch ( err ) {
			console.error( 'Token generation error:', err );
			
			// Show more detailed error message
			if ( err.code === 'rest_forbidden' || err.status === 401 ) {
				setError( 
					err.message || __( 'You need to be logged in as an administrator to generate JWT tokens.', 'mcp-for-woocommerce' )
				);
			} else if ( err.status === 403 ) {
				setError( __( 'You do not have permission to generate JWT tokens. Admin privileges required.', 'mcp-for-woocommerce' ) );
			} else {
				setError(
					err.message || __( 'Error generating token', 'mcp-for-woocommerce' )
				);
			}
		} finally {
			setLoading( false );
		}
	};

	const revokeToken = async ( jti ) => {
		try {
			const fallbackUrl = window.mcpfowoSettings?.restFallbackUrl;
			
			try {
				await apiFetch( {
					path: '/jwt-auth/v1/revoke',
					method: 'POST',
					data: {
						jti: jti,
					},
					includeCredentials: true,
				} );
			} catch (firstError) {
				// If primary path fails, try fallback
				if (fallbackUrl) {
					await apiFetch( {
						url: fallbackUrl + '/jwt-auth/v1/revoke',
						method: 'POST',
						data: {
							jti: jti,
						},
						includeCredentials: true,
					} );
				} else {
					throw firstError;
				}
			}
			
			// Refresh the tokens list
			fetchTokens();
		} catch ( err ) {
			console.error( 'Token revocation error:', err );
			
			// Show more detailed error message
			if ( err.code === 'rest_forbidden' || err.status === 401 ) {
				setError( 
					err.message || __( 'You need to be logged in as an administrator to revoke JWT tokens.', 'mcp-for-woocommerce' )
				);
			} else if ( err.status === 403 ) {
				setError( __( 'You do not have permission to revoke JWT tokens. Admin privileges required.', 'mcp-for-woocommerce' ) );
			} else {
				setError(
					err.message || __( 'Error revoking token', 'mcp-for-woocommerce' )
				);
			}
		}
	};

	const copyToClipboard = ( text ) => {
		// Try using the Clipboard API first
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then(
				() => {
					setCopySuccess( true );
				},
				() => {
					// Fallback if Clipboard API fails
					fallbackCopyToClipboard( text );
				}
			);
		} else {
			// Fallback for non-secure contexts or when Clipboard API is not available
			fallbackCopyToClipboard( text );
		}
	};

	const fallbackCopyToClipboard = ( text ) => {
		// Create a temporary textarea element
		const textArea = document.createElement( 'textarea' );
		textArea.value = text;

		// Make the textarea out of viewport
		textArea.style.position = 'fixed';
		textArea.style.left = '-999999px';
		textArea.style.top = '-999999px';
		document.body.appendChild( textArea );

		// Select and copy the text
		textArea.focus();
		textArea.select();

		try {
			document.execCommand( 'copy' );
			setCopySuccess( true );
		} catch ( err ) {
			setError( __( 'Failed to copy to clipboard', 'mcp-for-woocommerce' ) );
		}

		// Clean up
		document.body.removeChild( textArea );
	};

	const formatDate = ( timestamp ) => {
		return format( new Date( timestamp * 1000 ), 'PPpp' );
	};

	// UPDATED: Format duration s podporou "never"
	const formatDuration = ( value ) => {
		if ( value === 'never' || value === null ) {
			return __( 'Never expires', 'mcp-for-woocommerce' );
		}
		
		const seconds = parseInt( value );
		if ( isNaN( seconds ) ) {
			return __( 'Never expires', 'mcp-for-woocommerce' );
		}
		
		const hours = Math.floor( seconds / 3600 );
		if ( hours >= 24 ) {
			const days = Math.floor( hours / 24 );
			return days === 1
				? __( '1 day', 'mcp-for-woocommerce' )
				: `${ days } ${ __( 'days', 'mcp-for-woocommerce' ) }`;
		}
		return hours === 1
			? __( '1 hour', 'mcp-for-woocommerce' )
			: `${ hours } ${ __( 'hours', 'mcp-for-woocommerce' ) }`;
	};

	return (
		<div className="mcp-settings-tab">
			<Card>
				<CardBody>
					<h2>{ __( 'Authentication Tokens', 'mcp-for-woocommerce' ) }</h2>

					<div
						className="mcp-info-section"
						style={ {
							marginBottom: '24px',
							padding: '16px',
							backgroundColor: '#f9f9f9',
							border: '1px solid #ddd',
							borderRadius: '4px',
						} }
					>
						<h3>
							{ __(
								'What are Authentication Tokens?',
								'mcp-for-woocommerce'
							) }
						</h3>
						<p>
							{ __(
								'MCP authentication tokens are secure, temporary credentials that allow external MCP clients to the WordPress MCP server',
								'mcp-for-woocommerce'
							) }
						</p>

						<p>
							{ __(
								'These tokens are implemented using JWT (JSON Web Tokens), providing secure and stateless authentication.',
								'mcp-for-woocommerce'
							) }
						</p>

						<h4>{ __( 'How They Work', 'mcp-for-woocommerce' ) }</h4>
						<ul style={ { marginLeft: '20px' } }>
							<li>
								{ __(
									'Tokens are generated with your current user permissions and expire automatically',
									'mcp-for-woocommerce'
								) }
							</li>
							<li>
								{ __(
									'They provide access to MCP route endpoints for your WordPress content',
									'mcp-for-woocommerce'
								) }
							</li>
							<li>
								{ __(
									'Each token is unique and can be revoked individually if compromised',
									'mcp-for-woocommerce'
								) }
							</li>
							<li>
								{ __(
									'Tokens automatically inherit your user role and capabilities',
									'mcp-for-woocommerce'
								) }
							</li>
						</ul>

						<h4>
							{ __( 'Security Best Practices', 'mcp-for-woocommerce' ) }
						</h4>
						<div
							style={ {
								padding: '12px',
								backgroundColor: '#fff3cd',
								border: '1px solid #ffeaa7',
								borderRadius: '4px',
								marginBottom: '12px',
							} }
						>
							<strong>
								{ __(
									'⚠️ Important Security Notes:',
									'mcp-for-woocommerce'
								) }
							</strong>
							<ul
								style={ {
									marginLeft: '20px',
									marginTop: '8px',
								} }
							>
								<li>
									{ __(
										'Never share tokens in public repositories, emails, or chat messages',
										'mcp-for-woocommerce'
									) }
								</li>
								<li>
									{ __(
										'Use the shortest expiration time that meets your needs',
										'mcp-for-woocommerce'
									) }
								</li>
								<li>
									{ __(
										'Revoke tokens immediately if you suspect they may be compromised',
										'mcp-for-woocommerce'
									) }
								</li>
								<li>
									{ __(
										'Regularly review and clean up unused or expired tokens',
										'mcp-for-woocommerce'
									) }
								</li>
							</ul>
						</div>

						{/* NOVÉ: Never-expiring tokens warning */}
						<div
							style={ {
								padding: '12px',
								backgroundColor: '#f8d7da',
								border: '1px solid #f5c6cb',
								borderRadius: '4px',
								marginTop: '12px',
								marginBottom: '12px',
							} }
						>
							<strong>
								{ __( '🚨 Never-Expiring Tokens:', 'mcp-for-woocommerce' ) }
							</strong>
							<ul style={ { marginLeft: '20px', marginTop: '8px' } }>
								<li>
									{ __(
										'Should only be used in highly controlled environments',
										'mcp-for-woocommerce'
									) }
								</li>
								<li>
									{ __(
										'Must be manually revoked if compromised',
										'mcp-for-woocommerce'
									) }
								</li>
								<li>
									{ __(
										'Consider using long-lived tokens (24 hours) instead',
										'mcp-for-woocommerce'
									) }
								</li>
								<li>
									{ __(
										'Regularly audit and rotate never-expiring tokens',
										'mcp-for-woocommerce'
									) }
								</li>
							</ul>
						</div>

						<div
							style={ {
								padding: '12px',
								backgroundColor: '#e8f4fd',
								border: '1px solid #b3d9ff',
								borderRadius: '4px',
								marginTop: '12px',
								marginBottom: '12px',
							} }
						>
							<strong>
								{ __( 'ℹ️ Important Note:', 'mcp-for-woocommerce' ) }
							</strong>
							<p
								style={ {
									marginTop: '8px',
									marginBottom: '0',
								} }
							>
								{ __(
									'These tokens are exclusively for MCP protocol access (stdio and streamable/HTTP). They will not work with WordPress REST API endpoints or other authentication systems.',
									'mcp-for-woocommerce'
								) }
							</p>
						</div>

						<p
							style={ {
								marginTop: '16px',
								fontStyle: 'italic',
								color: '#666',
							} }
						>
							{ __(
								'Remember: These tokens provide access based on your user permissions. If you can see it in WordPress, applications using your token can see it too.',
								'mcp-for-woocommerce'
							) }
						</p>
					</div>

					<p>
						{ __(
							'Generate tokens below to allow secure access to your WordPress content through the MCP protocol.',
							'mcp-for-woocommerce'
						) }
					</p>

					{/* Token Limit Information */}
					<div
						style={ {
							padding: '12px',
							backgroundColor: '#e7f3ff',
							border: '1px solid #b3d9ff',
							borderRadius: '4px',
							marginBottom: '16px',
						} }
					>
						<strong>
							{ __(
								'ℹ️ Token Limit:',
								'mcp-for-woocommerce'
							) }
						</strong>
						<p style={ { marginTop: '8px', marginBottom: '0', fontSize: '14px' } }>
							{ __(
								'Maximum 10 active tokens allowed per user. When generating a new token at the limit, oldest revoked tokens will be automatically removed to make room. If no revoked tokens exist, new token generation will be blocked - you must revoke some existing active tokens first.',
								'mcp-for-woocommerce'
							) }
						</p>
					</div>

					<div className="mcp-form-field">
						<SelectControl
							label={ __( 'Token Duration', 'mcp-for-woocommerce' ) }
							value={ selectedDuration }
							options={ durationOptions }
							onChange={ ( value ) => {
								// UPDATED: Handle both string and number values
								setSelectedDuration( value );
							} }
							help={ __(
								'Choose how long the token will remain valid',
								'mcp-for-woocommerce'
							) }
						/>
					</div>

					{/* NOVÉ: Security warning for never expire */}
					{ selectedDuration === 'never' && (
						<div
							style={ {
								padding: '12px',
								backgroundColor: '#fff3cd',
								border: '1px solid #ffeaa7',
								borderRadius: '4px',
								marginBottom: '12px',
							} }
						>
							<strong>
								{ __(
									'⚠️ Security Warning:',
									'mcp-for-woocommerce'
								) }
							</strong>
							<p style={ { marginTop: '8px', marginBottom: '0' } }>
								{ __(
									'Never-expiring tokens pose significant security risks. If compromised, they cannot be invalidated through expiration. Only use this option if you fully understand the security implications and have proper token management procedures in place.',
									'mcp-for-woocommerce'
								) }
							</p>
						</div>
					) }

					<div className="mcp-form-field">
						<Button
							isPrimary
							onClick={ generateToken }
							isBusy={ loading }
							disabled={ loading }
						>
							{ __( 'Generate New Token', 'mcp-for-woocommerce' ) }
						</Button>
					</div>

					{ error && (
						<div className="mcp-error-message">{ error }</div>
					) }

					{ token && (
						<div className="mcp-token-section">
							<div className="mcp-token-container">
								<h3>
									{ __( 'Generated Token', 'mcp-for-woocommerce' ) }
								</h3>
								<TextareaControl
									readOnly
									value={ token.token }
									rows={ 4 }
								/>
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										gap: '10px',
									} }
								>
									<Button
										isSecondary
										onClick={ () =>
											copyToClipboard( token.token )
										}
									>
										{ __( 'Copy', 'mcp-for-woocommerce' ) }
									</Button>
									{ copySuccess && (
										<span style={ { color: '#00a32a' } }>
											{ __(
												'Token copied!',
												'mcp-for-woocommerce'
											) }
										</span>
									) }
								</div>
								{/* UPDATED: Token description s podporou never expire */}
								<p className="description">
									{ token.expires_in === 'never' || token.never_expire
										? __( 'This token never expires', 'mcp-for-woocommerce' )
										: token.expires_in
										? `${ __(
											  'Expires in',
											  'mcp-for-woocommerce'
										  ) } ${ formatDuration(
											  token.expires_in
										  ) }`
										: __(
											  'Expires in 1 hour',
											  'mcp-for-woocommerce'
										  ) }
									{ token.expires_at && ! (token.never_expire || token.expires_in === 'never') && (
										<span>
											{ ' (' }
											{ formatDate( token.expires_at ) }
											{ ')' }
										</span>
									) }
								</p>
							</div>
						</div>
					) }

					<div className="mcp-tokens-list">
						<h3>{ __( 'Your Active Tokens', 'mcp-for-woocommerce' ) }</h3>
						<table className="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th>{ __( 'User', 'mcp-for-woocommerce' ) }</th>
									<th>
										{ __( 'Issued At', 'mcp-for-woocommerce' ) }
									</th>
									<th>
										{ __( 'Expires At', 'mcp-for-woocommerce' ) }
									</th>
									<th>{ __( 'Status', 'mcp-for-woocommerce' ) }</th>
									<th>
										{ __( 'Actions', 'mcp-for-woocommerce' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ tokens.slice( 0, 10 ).map( ( token ) => (
									<tr key={ token.jti }>
										<td>
											{ token.user.display_name }
											<br />
											<small style={ { color: '#666' } }>
												{ token.user.username }
											</small>
										</td>
										<td>
											{ formatDate( token.issued_at ) }
										</td>
										{/* UPDATED: Expires At column s podporou never expire */}
										<td>
											{ token.never_expire 
												? __( 'Never expires', 'mcp-for-woocommerce' )
												: formatDate( token.expires_at ) 
											}
										</td>
										{/* UPDATED: Status column s podporou never expire */}
										<td>
											{ token.revoked
												? __(
														'Revoked',
														'mcp-for-woocommerce'
												  )
												: token.never_expire
												? __(
														'Active (Never expires)',
														'mcp-for-woocommerce'
												  )
												: token.is_expired
												? __(
														'Expired',
														'mcp-for-woocommerce'
												  )
												: __(
														'Active',
														'mcp-for-woocommerce'
												  ) }
										</td>
										<td>
											{ ! token.revoked &&
												! token.is_expired && (
													<Button
														isDestructive
														isSmall
														onClick={ () =>
															revokeToken(
																token.jti
															)
														}
													>
														{ __(
															'Revoke',
															'mcp-for-woocommerce'
														) }
													</Button>
												) }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
						
						{/* Show info if there are more than 10 tokens */}
						{ tokens.length > 10 && (
							<p style={ { marginTop: '12px', color: '#666', fontStyle: 'italic' } }>
								{ __( 'Showing first 10 tokens. Total tokens:', 'mcp-for-woocommerce' ) } { tokens.length }
							</p>
						) }
					</div>
				</CardBody>
			</Card>
		</div>
	);
};

export default AuthenticationTokensTab;
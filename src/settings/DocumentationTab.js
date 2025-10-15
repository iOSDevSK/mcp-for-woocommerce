/**
 * WordPress dependencies
 */
import { Card, CardHeader, CardBody, Spinner, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { marked } from 'marked';

/**
 * Documentation Tab Component
 */
const DocumentationTab = () => {
	const [ content, setContent ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ copySuccess, setCopySuccess ] = useState( false );

	// Configure marked options for better security and rendering
	marked.setOptions( {
		breaks: true,
		gfm: true,
		sanitize: false, // We control the markdown content
	} );

	useEffect( () => {
		// Load the markdown documentation
		const loadDocumentation = async () => {
			try {
				setIsLoading( true );
				setError( null );

				// Fetch the markdown file from the plugin directory
				const response = await fetch(
					`${ window.mcpfowoSettings.pluginUrl }/client-setup.md`
				);

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				const markdownText = await response.text();
				
				// Replace placeholder with actual site URL
				const siteUrl = window.location.origin;
				const processedMarkdown = markdownText.replace(
					/\{\{your-website\.com\}\}/g,
					siteUrl
				);
				
				const htmlContent = marked( processedMarkdown );
				setContent( htmlContent );
			} catch ( err ) {
				console.error( 'Error loading documentation:', err );
				setError( err.message );
			} finally {
				setIsLoading( false );
			}
		};

		loadDocumentation();
	}, [] );

	// Add copy buttons to code blocks after content loads
	useEffect( () => {
		if ( content ) {
			// Wait for DOM to update
			setTimeout( () => {
				const preElements = document.querySelectorAll( '.mcpfowo-documentation pre' );
				preElements.forEach( ( pre ) => {
					// Skip if copy button already exists
					if ( pre.querySelector( '.copy-button' ) ) {
						return;
					}

					// Create copy button
					// Get the code text before adding the copy button
					const codeText = pre.textContent || pre.innerText;
					
					const copyButton = document.createElement( 'button' );
					copyButton.className = 'copy-button';
					copyButton.textContent = 'Copy';
					copyButton.onclick = () => {
						copyToClipboard( codeText );
					};

					pre.appendChild( copyButton );
				} );
			}, 100 );
		}
	}, [ content ] );

	// Clear copy success message after 2 seconds
	useEffect( () => {
		if ( copySuccess ) {
			const timer = setTimeout( () => {
				setCopySuccess( false );
			}, 2000 );
			return () => clearTimeout( timer );
		}
	}, [ copySuccess ] );

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

	if ( isLoading ) {
		return (
			<Card>
				<CardBody>
					<div className="documentation-loading">
						<Spinner />
						<p>
							{ __(
								'Loading documentation...',
								'mcp-for-woocommerce'
							) }
						</p>
					</div>
				</CardBody>
			</Card>
		);
	}

	if ( error ) {
		return (
			<Card>
				<CardHeader>
					<h2>{ __( 'Documentation', 'mcp-for-woocommerce' ) }</h2>
				</CardHeader>
				<CardBody>
					<div className="documentation-error">
						<p>
							{ __(
								'Error loading documentation:',
								'mcp-for-woocommerce'
							) }{ ' ' }
							{ error }
						</p>
						<p>
							{ __(
								'Please check that the documentation file exists and is accessible.',
								'mcp-for-woocommerce'
							) }
						</p>
					</div>
				</CardBody>
			</Card>
		);
	}

	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Documentation', 'mcp-for-woocommerce' ) }</h2>
			</CardHeader>
			<CardBody>
				{ copySuccess && (
					<div className="notice notice-success inline" style={{ marginTop: '10px', marginBottom: '20px' }}>
						<p>{ __( 'Configuration copied to clipboard!', 'mcp-for-woocommerce' ) }</p>
					</div>
				) }
				<div
					className="mcpfowo-documentation"
					dangerouslySetInnerHTML={ { __html: content } }
				/>
				<style>{ `
					.mcpfowo-documentation pre {
						position: relative;
					}
					.mcpfowo-documentation pre:hover .copy-button {
						opacity: 1;
					}
					.copy-button {
						position: absolute;
						top: 8px;
						right: 8px;
						opacity: 0;
						transition: opacity 0.2s;
						background: #0073aa;
						color: white;
						border: none;
						padding: 4px 8px;
						font-size: 11px;
						border-radius: 3px;
						cursor: pointer;
					}
					.copy-button:hover {
						background: #005a87;
					}
				` }</style>
			</CardBody>
		</Card>
	);
};

export default DocumentationTab;

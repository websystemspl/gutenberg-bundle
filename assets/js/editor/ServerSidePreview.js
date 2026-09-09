/**
 * Renders a PHP block by asking the server, the same contract as WordPress' ServerSideRender.
 *
 * The canvas therefore shows the real Twig output rather than a JavaScript approximation of it,
 * which is what keeps custom blocks free of duplicated markup.
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const DEBOUNCE_MS = 350;

export default function ServerSidePreview( { endpoint, name, attributes, innerHtml = '', markup } ) {
	const [ state, setState ] = useState( { status: 'loading', html: '', error: null } );
	const requestId = useRef( 0 );

	// Either one PHP block type, or arbitrary markup the editor cannot expand on its own.
	const payload = JSON.stringify( markup ? { markup } : { name, attributes, innerHtml } );

	useEffect( () => {
		if ( ! endpoint ) {
			setState( { status: 'error', html: '', error: __( 'The preview endpoint is not available.' ) } );
			return undefined;
		}

		const current = ++requestId.current;
		const timer = setTimeout( () => {
			fetch( endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
				body: payload,
			} )
				.then( ( response ) => response.json() )
				.then( ( data ) => {
					if ( current !== requestId.current ) {
						return;
					}

					setState( {
						status: data.error ? 'error' : 'ready',
						html: data.html || '',
						error: data.error || null,
					} );
				} )
				.catch( ( error ) => {
					if ( current === requestId.current ) {
						setState( { status: 'error', html: '', error: error.message } );
					}
				} );
		}, DEBOUNCE_MS );

		return () => clearTimeout( timer );
	}, [ endpoint, payload ] );

	if ( state.status === 'loading' ) {
		return (
			<div className="wsg-ssr wsg-ssr--loading">
				<Spinner />
			</div>
		);
	}

	if ( state.error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ state.error }
			</Notice>
		);
	}

	if ( ! state.html.trim() ) {
		return <div className="wsg-ssr wsg-ssr--empty">{ __( 'This block renders nothing yet.' ) }</div>;
	}

	return <div className="wsg-ssr" dangerouslySetInnerHTML={ { __html: state.html } } />;
}

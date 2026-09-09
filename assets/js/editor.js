/**
 * Entry point of the block editor bundle.
 *
 * Boots one editor per `[data-gutenberg-editor]` element and keeps the associated textarea in
 * sync, so the surrounding Symfony form submits normally with no extra JavaScript.
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { registerCoreBlocks } from '@wordpress/block-library';
import '@wordpress/format-library';

import Editor from './editor/Editor';
import { fetchConfiguration } from './editor/configuration';
import { registerCustomBlocks, registerBlockCategories } from './editor/registerCustomBlocks';
import { registerReusableBlocks } from './editor/registerReusableBlocks';
import { enableBlockTypeTranslation } from './editor/translateBlockTypes';
import { applyTranslations } from './editor/translations';

import '../css/editor.scss';

// Some admin panels (EasyAdmin, for one) attach the bundle themselves. Loading it twice would
// re-register every block type, so the second copy stands down.
const ALREADY_BOOTED = '__webSystemsGutenbergBooted';
const isDuplicate = Boolean( window[ ALREADY_BOOTED ] );
window[ ALREADY_BOOTED ] = true;

const mounted = new WeakSet();
let coreBlocksRegistered = false;

function registerOnce( config ) {
	if ( ! coreBlocksRegistered ) {
		// Must precede registration: the filter translates metadata as each block registers.
		enableBlockTypeTranslation();
		registerCoreBlocks();
		coreBlocksRegistered = true;
	}

	registerBlockCategories( config );
	registerCustomBlocks( config );
	registerReusableBlocks( config );
}

async function mount( element ) {
	if ( mounted.has( element ) ) {
		return;
	}

	mounted.add( element );

	const textarea = document.getElementById( element.dataset.gutenbergTarget );

	if ( ! textarea ) {
		// Without a target there is nothing to persist into; leave the textarea visible.
		element.remove();
		return;
	}

	try {
		const config = await fetchConfiguration( element.dataset.gutenbergConfigUrl );

		// Must precede block registration: block titles are translated when registered.
		await applyTranslations( config );

		registerOnce( config );

		const fieldSettings = JSON.parse( element.dataset.gutenbergSettings || '{}' );
		const height = parseInt( element.dataset.gutenbergHeight || '720', 10 );

		createRoot( element ).render(
			<Editor
				config={ config }
				fieldSettings={ fieldSettings }
				initialContent={ textarea.value }
				height={ height }
				onPersist={ ( content ) => {
					if ( textarea.value === content ) {
						return;
					}

					textarea.value = content;
					textarea.dispatchEvent( new Event( 'input', { bubbles: true } ) );
					textarea.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				} }
			/>
		);
	} catch ( error ) {
		// Fall back to the plain textarea rather than leaving an empty box behind.
		element.innerHTML = `<p class="wsg-editor__error">${ error.message }</p>`;
		textarea.hidden = false;
	}
}

function boot( root = document ) {
	root.querySelectorAll( '[data-gutenberg-editor]' ).forEach( mount );
}

domReady( () => {
	if ( isDuplicate ) {
		return;
	}

	boot();

	// Fields added later (EasyAdmin collections, Turbo/htmx navigations) mount automatically.
	new MutationObserver( ( mutations ) => {
		mutations.forEach( ( mutation ) =>
			mutation.addedNodes.forEach( ( node ) => {
				if ( node.nodeType !== Node.ELEMENT_NODE ) {
					return;
				}

				if ( node.matches?.( '[data-gutenberg-editor]' ) ) {
					mount( node );
				}

				boot( node );
			} )
		);
	} ).observe( document.body, { childList: true, subtree: true } );
} );

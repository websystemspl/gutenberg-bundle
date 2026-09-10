/**
 * Turns the JSON schemas published by PHP into registered block types.
 *
 * Every custom block goes through the same generic edit component, so adding a block on the
 * PHP side never requires touching or rebuilding this JavaScript.
 */
import { registerBlockType, getBlockType } from '@wordpress/blocks';
import { dispatch, select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import DynamicBlockEdit from './DynamicBlockEdit';
import { resolveIcon } from './resolveIcon';

export function registerCustomBlocks( config ) {
	const endpoints = config.endpoints || {};

	( config.customBlocks || [] ).forEach( ( schema ) => {
		if ( getBlockType( schema.name ) ) {
			return; // Already registered by another field on the same page.
		}

		registerBlockType( schema.name, {
			apiVersion: 3,
			title: schema.title,
			icon: resolveIcon( schema.icon ),
			category: schema.category,
			description: schema.description || undefined,
			keywords: schema.keywords || [],
			attributes: schema.attributes || {},
			supports: { html: false, ...( schema.supports || {} ) },
			edit: ( props ) => (
				<DynamicBlockEdit { ...props } schema={ schema } endpoints={ endpoints } />
			),
			// Dynamic blocks store attributes only; the markup comes from PHP at render time.
			save: () => ( schema.innerBlocks ? <InnerBlocks.Content /> : null ),
		} );
	} );
}

export function registerBlockCategories( config ) {
	const extra = config.blockCategories || [];
	const existing = select( 'core/blocks' ).getCategories();
	const known = new Set( existing.map( ( category ) => category.slug ) );
	const additions = extra.filter( ( category ) => ! known.has( category.slug ) );

	// The default categories are built when @wordpress/blocks is imported, which happens before
	// the catalogue is loaded, so their titles are still English at this point.
	const translated = existing.map( ( category ) => ( {
		...category,
		title: 'string' === typeof category.title ? __( category.title ) : category.title,
	} ) );

	additions.forEach( ( category ) => {
		if ( category.icon ) {
			category.icon = resolveIcon( category.icon );
		}
	} );

	dispatch( 'core/blocks' ).setCategories( [ ...translated, ...additions ] );
}

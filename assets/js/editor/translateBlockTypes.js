/**
 * Translates the metadata of core blocks.
 *
 * Block titles, descriptions and keywords come from block.json, which WordPress translates on
 * the server while building the block registry. There is no such step here, so the strings are
 * passed through @wordpress/i18n as each block registers — a string missing from the catalogue
 * simply stays as it is.
 *
 * Only core blocks are touched: blocks declared in PHP already carry the wording their author
 * chose, and running them through the catalogue could replace it by accident.
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const NAMESPACE = 'web-systems/gutenberg/translate-block-types';

const translate = ( value ) => ( 'string' === typeof value && value ? __( value ) : value );

export function enableBlockTypeTranslation() {
	addFilter( 'blocks.registerBlockType', NAMESPACE, ( settings, name ) => {
		if ( ! name?.startsWith( 'core/' ) ) {
			return settings;
		}

		return {
			...settings,
			title: translate( settings.title ),
			description: translate( settings.description ),
			keywords: Array.isArray( settings.keywords )
				? settings.keywords.map( translate )
				: settings.keywords,
			styles: Array.isArray( settings.styles )
				? settings.styles.map( ( style ) => ( { ...style, label: translate( style.label ) } ) )
				: settings.styles,
			variations: Array.isArray( settings.variations )
				? settings.variations.map( ( variation ) => ( {
						...variation,
						title: translate( variation.title ),
						description: translate( variation.description ),
				  } ) )
				: settings.variations,
		};
	} );
}

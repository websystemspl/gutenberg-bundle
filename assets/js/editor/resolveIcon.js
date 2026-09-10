/**
 * Turns the icon declared in PHP into something the editor can actually draw.
 *
 * `#[AsBlock(icon: …)]` accepts a name, and WordPress' own convention is a Dashicon slug. But
 * Dashicons are an icon *font* shipped with WordPress core, not with any npm package: the
 * component renders `<span class="dashicons-cover-image">` and, with no font behind it, nothing
 * appears. Names are therefore resolved against @wordpress/icons, which ships real SVGs.
 *
 * Accepted values, in order of preference:
 *   - a name from @wordpress/icons, in camelCase (`listView`) or kebab-case (`list-view`);
 *   - a Dashicon slug for the common ones, mapped below;
 *   - raw SVG markup, rendered as-is;
 *   - anything unknown falls back to the default block icon rather than to a blank space.
 */
import * as icons from '@wordpress/icons';
import { createElement } from '@wordpress/element';

/** Dashicon slugs whose @wordpress/icons counterpart carries a different name. */
const DASHICON_ALIASES = {
	'admin-appearance': 'brush',
	'admin-comments': 'comment',
	'admin-generic': 'cog',
	'admin-links': 'link',
	'admin-page': 'page',
	'admin-site': 'globe',
	'admin-users': 'people',
	'align-pull-left': 'pullLeft',
	'align-pull-right': 'pullRight',
	'analytics': 'chartBar',
	'archive': 'archive',
	'businessperson': 'people',
	'calendar-alt': 'calendar',
	'camera': 'camera',
	'cart': 'cart',
	'cover-image': 'cover',
	'edit': 'edit',
	'editor-code': 'code',
	'editor-ol': 'formatListNumbered',
	'editor-quote': 'quote',
	'editor-table': 'table',
	'editor-ul': 'formatListBullets',
	'email': 'envelope',
	'embed-generic': 'embed',
	'format-gallery': 'gallery',
	'format-image': 'image',
	'format-quote': 'quote',
	'format-video': 'video',
	'grid-view': 'grid',
	'groups': 'people',
	'heading': 'heading',
	'id': 'people',
	'images-alt2': 'gallery',
	'index-card': 'postList',
	'info': 'info',
	'layout': 'layout',
	'list-view': 'listView',
	'location': 'mapMarker',
	'media-default': 'media',
	'media-document': 'page',
	'media-spreadsheet': 'table',
	'megaphone': 'megaphone',
	'menu': 'menu',
	'money-alt': 'payment',
	'phone': 'mobile',
	'screenoptions': 'preformatted',
	'search': 'search',
	'star-filled': 'starFilled',
	'star-empty': 'starEmpty',
	'tag': 'tag',
	'testimonial': 'quote',
	'text-page': 'postContent',
	'thumbs-up': 'thumbsUp',
	'video-alt3': 'video',
	'welcome-widgets-menus': 'widget',
};

const FALLBACK = icons.blockDefault;

const toCamelCase = ( value ) =>
	value.replace( /[-_]([a-z0-9])/g, ( _, character ) => character.toUpperCase() );

export function resolveIcon( icon ) {
	if ( ! icon ) {
		return FALLBACK;
	}

	// Already a React element or an icon object: pass it straight through.
	if ( 'string' !== typeof icon ) {
		return icon;
	}

	const trimmed = icon.trim();

	if ( trimmed.startsWith( '<svg' ) ) {
		return createElement( 'span', {
			className: 'wsg-block-icon',
			dangerouslySetInnerHTML: { __html: trimmed },
		} );
	}

	const candidates = [ DASHICON_ALIASES[ trimmed ], trimmed, toCamelCase( trimmed ) ];

	for ( const candidate of candidates ) {
		if ( candidate && icons[ candidate ] ) {
			return icons[ candidate ];
		}
	}

	return FALLBACK;
}

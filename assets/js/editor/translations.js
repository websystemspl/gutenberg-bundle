/**
 * Loads the editor's interface language.
 *
 * The message map comes from the official WordPress.org language pack installed by
 * `bin/console gutenberg:translations`, in exactly the format @wordpress/i18n expects, so the
 * editor speaks the same language as WordPress itself.
 */
import { setLocaleData } from '@wordpress/i18n';

const loaded = new Set();

export async function applyTranslations( config ) {
	const translations = config.translations;

	if ( ! translations?.available || ! translations.url || loaded.has( translations.locale ) ) {
		return;
	}

	loaded.add( translations.locale );

	try {
		const response = await fetch( translations.url, {
			headers: { Accept: 'application/json' },
			credentials: 'same-origin',
		} );

		if ( response.ok ) {
			setLocaleData( await response.json() );
		}
	} catch {
		// A missing catalogue is not fatal: the editor stays in English.
	}
}

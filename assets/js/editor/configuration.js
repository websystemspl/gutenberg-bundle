/**
 * Loads the editor configuration published by EditorConfigController and caches it, so that
 * several fields on one page share a single request.
 */
const cache = new Map();

export function fetchConfiguration( url ) {
	if ( ! cache.has( url ) ) {
		cache.set(
			url,
			fetch( url, {
				headers: { Accept: 'application/json' },
				credentials: 'same-origin',
			} ).then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error(
						`The editor configuration endpoint returned ${ response.status }.`
					);
				}

				return response.json();
			} )
		);
	}

	return cache.get( url );
}

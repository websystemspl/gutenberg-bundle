/**
 * Bridges the editor's media handling to the bundle's own media endpoint.
 *
 * Providing `mediaUpload` in the editor settings is what makes drag-and-drop, paste and the
 * "Upload" buttons of the core image/gallery/cover blocks work without WordPress.
 */

export async function uploadFile( endpoint, file ) {
	const body = new FormData();
	body.append( 'file', file );

	const response = await fetch( endpoint, {
		method: 'POST',
		body,
		credentials: 'same-origin',
		headers: { Accept: 'application/json' },
	} );

	const payload = await response.json().catch( () => ( {} ) );

	if ( ! response.ok ) {
		throw new Error( payload.error || `Upload failed with status ${ response.status }.` );
	}

	return toMediaObject( payload );
}

export async function listMedia( endpoint ) {
	const response = await fetch( endpoint, {
		credentials: 'same-origin',
		headers: { Accept: 'application/json' },
	} );

	if ( ! response.ok ) {
		return [];
	}

	const payload = await response.json();

	return ( payload.items || [] ).map( toMediaObject );
}

function toMediaObject( item ) {
	return {
		id: item.id,
		url: item.url,
		alt: item.alt || '',
		title: item.name || '',
		filename: item.name || '',
		mime: item.mime,
		type: ( item.mime || '' ).split( '/' )[ 0 ],
		subtype: ( item.mime || '' ).split( '/' )[ 1 ],
		width: item.width,
		height: item.height,
		caption: '',
	};
}

/**
 * Builds the `mediaUpload` callback expected by the block editor settings.
 */
export function createMediaUploader( endpoint ) {
	if ( ! endpoint ) {
		return undefined;
	}

	return async ( { filesList, onFileChange, onError, maxUploadFileSize, allowedTypes } ) => {
		const files = Array.from( filesList || [] );
		const uploaded = [];

		for ( const file of files ) {
			if ( maxUploadFileSize && file.size > maxUploadFileSize ) {
				onError?.( {
					code: 'SIZE_ABOVE_LIMIT',
					message: `"${ file.name }" is larger than the allowed size.`,
					file,
				} );
				continue;
			}

			if ( allowedTypes?.length && ! allowedTypes.some( ( type ) => file.type.startsWith( type.split( '/' )[ 0 ] ) ) ) {
				onError?.( {
					code: 'MIME_TYPE_NOT_ALLOWED',
					message: `"${ file.name }" is not an allowed file type.`,
					file,
				} );
				continue;
			}

			try {
				uploaded.push( await uploadFile( endpoint, file ) );
				onFileChange?.( [ ...uploaded ] );
			} catch ( error ) {
				onError?.( { code: 'UPLOAD_FAILED', message: error.message, file } );
			}
		}

		return uploaded;
	};
}

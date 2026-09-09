/**
 * Image picker backed by the bundle's media endpoint: pick an existing file or upload a new one.
 */
import { useState, useEffect } from '@wordpress/element';
import { BaseControl, Button, Modal, Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { listMedia, uploadFile } from './media';

export default function MediaControl( { label, help, value, endpoint, onChange } ) {
	const [ isOpen, setOpen ] = useState( false );

	return (
		<BaseControl __nextHasNoMarginBottom label={ label } help={ help } id={ `wsg-media-${ label }` }>
			<div className="wsg-media">
				{ value?.url ? (
					<div className="wsg-media__preview">
						<img src={ value.url } alt={ value.alt || '' } />
					</div>
				) : (
					<p className="wsg-media__empty">{ __( 'No image selected.' ) }</p>
				) }

				<div className="wsg-media__actions">
					<Button variant="secondary" size="compact" onClick={ () => setOpen( true ) } disabled={ ! endpoint }>
						{ value?.url ? __( 'Replace' ) : __( 'Select image' ) }
					</Button>
					{ value?.url && (
						<Button variant="tertiary" size="compact" isDestructive onClick={ () => onChange( null ) }>
							{ __( 'Remove' ) }
						</Button>
					) }
				</div>

				{ ! endpoint && (
					<p className="wsg-media__empty">{ __( 'The media endpoint is disabled in the bundle configuration.' ) }</p>
				) }
			</div>

			{ isOpen && (
				<MediaLibraryModal
					endpoint={ endpoint }
					onClose={ () => setOpen( false ) }
					onSelect={ ( item ) => {
						onChange( {
							id: item.id,
							url: item.url,
							alt: item.alt || '',
							width: item.width || null,
							height: item.height || null,
						} );
						setOpen( false );
					} }
				/>
			) }
		</BaseControl>
	);
}

function MediaLibraryModal( { endpoint, onSelect, onClose } ) {
	const [ items, setItems ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ isUploading, setUploading ] = useState( false );

	const reload = () => {
		setItems( null );
		listMedia( endpoint ).then( setItems ).catch( ( e ) => setError( e.message ) );
	};

	useEffect( reload, [ endpoint ] );

	const handleUpload = async ( event ) => {
		const files = Array.from( event.target.files || [] );

		if ( ! files.length ) {
			return;
		}

		setUploading( true );
		setError( null );

		try {
			for ( const file of files ) {
				await uploadFile( endpoint, file );
			}
			reload();
		} catch ( e ) {
			setError( e.message );
		} finally {
			setUploading( false );
		}
	};

	return (
		<Modal title={ __( 'Media library' ) } onRequestClose={ onClose } className="wsg-media-modal">
			<div className="wsg-media-modal__toolbar">
				<label className="wsg-media-modal__upload">
					<span className="components-button is-primary">{ __( 'Upload files' ) }</span>
					<input type="file" multiple accept="image/*" onChange={ handleUpload } hidden />
				</label>
				{ isUploading && <Spinner /> }
			</div>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ items === null ? (
				<Spinner />
			) : (
				<div className="wsg-media-modal__grid">
					{ items.map( ( item ) => (
						<button
							key={ item.id }
							type="button"
							className="wsg-media-modal__item"
							onClick={ () => onSelect( item ) }
						>
							<img src={ item.url } alt={ item.alt || item.title } />
							<span>{ item.title }</span>
						</button>
					) ) }
					{ items.length === 0 && <p>{ __( 'The library is empty.' ) }</p> }
				</div>
			) }
		</Modal>
	);
}

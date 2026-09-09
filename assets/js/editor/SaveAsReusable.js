/**
 * Turns the current block selection into a reusable block.
 *
 * WordPress does this through the entity store; here the selected blocks are serialised, sent
 * to the bundle's endpoint and replaced in place by a core/block reference, so the document
 * ends up holding exactly the markup WordPress would have written.
 */
import { useState } from '@wordpress/element';
import { useDispatch, useRegistry } from '@wordpress/data';
import { BlockSettingsMenuControls, store as blockEditorStore } from '@wordpress/block-editor';
import { Modal, MenuItem, TextControl, Button, Notice, Flex, FlexItem } from '@wordpress/components';
import { createBlock, serialize } from '@wordpress/blocks';
import { symbol as symbolIcon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

export default function SaveAsReusable( { endpoint, onCreated } ) {
	const registry = useRegistry();
	const { replaceBlocks } = useDispatch( blockEditorStore );

	const [ selection, setSelection ] = useState( null );
	const [ title, setTitle ] = useState( '' );
	const [ error, setError ] = useState( null );
	const [ isSaving, setSaving ] = useState( false );

	if ( ! endpoint ) {
		return null;
	}

	const close = () => {
		setSelection( null );
		setError( null );
		setSaving( false );
	};

	const save = async () => {
		setSaving( true );
		setError( null );

		const blocks = registry.select( blockEditorStore ).getBlocksByClientId( selection );

		try {
			const response = await fetch( endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
				body: JSON.stringify( { title, content: serialize( blocks ) } ),
			} );

			const payload = await response.json().catch( () => ( {} ) );

			if ( ! response.ok ) {
				setError( payload.error || `HTTP ${ response.status }` );
				setSaving( false );
				return;
			}

			replaceBlocks( selection, createBlock( 'core/block', { ref: payload.ref } ) );
			onCreated?.( payload );
			close();
		} catch ( exception ) {
			setError( exception.message );
			setSaving( false );
		}
	};

	return (
		<>
			<BlockSettingsMenuControls>
				{ ( { selectedClientIds, onClose } ) => (
					<MenuItem
						icon={ symbolIcon }
						onClick={ () => {
							setSelection( selectedClientIds );
							setTitle( '' );
							onClose();
						} }
					>
						{ __( 'Create pattern' ) }
					</MenuItem>
				) }
			</BlockSettingsMenuControls>

			{ selection && (
				<Modal title={ __( 'Create pattern' ) } onRequestClose={ close } size="small">
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						autoFocus
						label={ __( 'Name' ) }
						value={ title }
						onChange={ setTitle }
						onKeyDown={ ( event ) => {
							if ( 'Enter' === event.key && title.trim() ) {
								save();
							}
						} }
					/>

					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }

					<Flex justify="flex-end" gap={ 2 } style={ { marginTop: '16px' } }>
						<FlexItem>
							<Button variant="tertiary" onClick={ close } disabled={ isSaving }>
								{ __( 'Cancel' ) }
							</Button>
						</FlexItem>
						<FlexItem>
							<Button
								variant="primary"
								onClick={ save }
								isBusy={ isSaving }
								disabled={ isSaving || ! title.trim() }
							>
								{ __( 'Create' ) }
							</Button>
						</FlexItem>
					</Flex>
				</Modal>
			) }
		</>
	);
}

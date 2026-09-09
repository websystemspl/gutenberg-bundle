/**
 * The editor shell mounted into one form field.
 *
 * Deliberately thin: block editing itself is entirely handled by the upstream
 * BlockEditorProvider/BlockCanvas pair, so a WordPress upgrade brings new editor behaviour
 * without changes here.
 */
import { useState, useRef, useMemo, useCallback, useEffect } from '@wordpress/element';
import {
	BlockEditorProvider,
	BlockCanvas,
	BlockList,
	BlockInspector,
	BlockTools,
	Inserter,
} from '@wordpress/block-editor';
import { SlotFillProvider, Popover, Button } from '@wordpress/components';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { parse, serialize, synchronizeBlocksWithTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	undo as undoIcon,
	redo as redoIcon,
	cog as cogIcon,
	plus as plusIcon,
	code as codeIcon,
	copy as copyIcon,
	fullscreen as fullscreenIcon,
} from '@wordpress/icons';
import { createMediaUploader } from './media';
import SaveAsReusable from './SaveAsReusable';
import { addReusableBlockVariation } from './registerReusableBlocks';

const HISTORY_LIMIT = 60;

export default function Editor( { config, fieldSettings, initialContent, height, onPersist } ) {
	const [ blocks, setBlocks ] = useState( () => initialBlocks( initialContent, fieldSettings ) );
	const [ isInspectorOpen, setInspectorOpen ] = useState( true );
	// Embedded in a form column the canvas is always narrower than the editor would like,
	// so offer a full-viewport mode.
	const [ isFullscreen, setFullscreen ] = useState( false );
	const [ isCodeView, setCodeView ] = useState( false );
	const [ , rerender ] = useState( 0 );

	const blocksRef = useRef( blocks );
	const history = useRef( { past: [], future: [] } );

	const settings = useMemo(
		() => buildSettings( config, fieldSettings ),
		[ config, fieldSettings ]
	);

	// BlockCanvas renders an unconstrained "default" root layout, which lets every block run
	// the full width of the canvas. Declaring a constrained root reproduces the content column
	// the front end uses, so the editor shows the real measure of the text.
	const rootLayout = useMemo( () => {
		const layout = settings.__experimentalFeatures?.layout;

		return layout?.contentSize || layout?.wideSize
			? { type: 'constrained', ...layout }
			: { type: 'default' };
	}, [ settings ] );

	// Only needed while the code view is open, but serialising is cheap next to a re-render.
	const markup = useMemo( () => serialize( blocks ), [ blocks ] );

	const commit = useCallback(
		( next, recordHistory ) => {
			if ( recordHistory ) {
				history.current.past.push( blocksRef.current );
				if ( history.current.past.length > HISTORY_LIMIT ) {
					history.current.past.shift();
				}
				history.current.future = [];
			}

			blocksRef.current = next;
			setBlocks( next );
			onPersist( serialize( next ) );
		},
		[ onPersist ]
	);

	const codeRef = useRef( null );
	const copyTimer = useRef( null );
	// 'idle' | 'copied' | 'manual' — never claim success the browser did not confirm.
	const [ copyState, setCopyState ] = useState( 'idle' );

	useEffect( () => () => clearTimeout( copyTimer.current ), [] );

	const copyMarkup = async () => {
		let copied = false;

		try {
			// Needs a secure context and a focused document; neither is guaranteed.
			await navigator.clipboard.writeText( markup );
			copied = true;
		} catch {
			codeRef.current?.focus();
			codeRef.current?.select();

			try {
				copied = document.execCommand( 'copy' );
			} catch {
				copied = false;
			}
		}

		if ( ! copied ) {
			// Leave the text selected so Ctrl+C finishes the job.
			codeRef.current?.focus();
			codeRef.current?.select();
		}

		setCopyState( copied ? 'copied' : 'manual' );
		clearTimeout( copyTimer.current );
		copyTimer.current = setTimeout( () => setCopyState( 'idle' ), 3000 );
	};

	const travel = ( from, to ) => {
		if ( ! history.current[ from ].length ) {
			return;
		}

		const next = history.current[ from ].pop();
		history.current[ to ].push( blocksRef.current );
		commit( next, false );
		rerender( ( n ) => n + 1 );
	};

	return (
		<ShortcutProvider
			className={ `wsg-editor__root${ isFullscreen ? ' wsg-editor__root--fullscreen' : '' }` }
		>
			<SlotFillProvider>
				<BlockEditorProvider
					value={ blocks }
					settings={ settings }
					onInput={ ( next ) => commit( next, false ) }
					onChange={ ( next ) => commit( next, true ) }
				>
					<div className="wsg-editor__toolbar">
						<Inserter
							position="bottom right"
							toggleProps={ {
								variant: 'primary',
								size: 'compact',
								icon: plusIcon,
								label: __( 'Add block' ),
								showTooltip: true,
							} }
						/>
						<div className="wsg-editor__toolbar-spacer" />
						<Button
							size="compact"
							variant="tertiary"
							icon={ undoIcon }
							label={ __( 'Undo' ) }
							disabled={ ! history.current.past.length }
							onClick={ () => travel( 'past', 'future' ) }
						/>
						<Button
							size="compact"
							variant="tertiary"
							icon={ redoIcon }
							label={ __( 'Redo' ) }
							disabled={ ! history.current.future.length }
							onClick={ () => travel( 'future', 'past' ) }
						/>
						<Button
							size="compact"
							variant="tertiary"
							icon={ codeIcon }
							label={ isCodeView ? __( 'Visual editor' ) : __( 'Code editor' ) }
							isPressed={ isCodeView }
							onClick={ () => setCodeView( ( on ) => ! on ) }
						/>
						<Button
							size="compact"
							variant="tertiary"
							icon={ fullscreenIcon }
							label={ isFullscreen ? __( 'Exit fullscreen' ) : __( 'Fullscreen' ) }
							isPressed={ isFullscreen }
							onClick={ () => setFullscreen( ( on ) => ! on ) }
						/>
						<Button
							size="compact"
							variant="tertiary"
							icon={ cogIcon }
							label={ __( 'Settings' ) }
							isPressed={ isInspectorOpen }
							onClick={ () => setInspectorOpen( ( open ) => ! open ) }
						/>
					</div>

					<div
						className="wsg-editor__body"
						style={ isFullscreen ? undefined : { height: `${ height }px` } }
					>
						{ isCodeView ? (
							<div className="wsg-editor__code">
								<div className="wsg-editor__code-bar">
									<span>{ __( 'Code editor' ) }</span>
									<Button
										size="compact"
										variant="secondary"
										icon={ copyIcon }
										onClick={ copyMarkup }
									>
										{ copyState === 'copied' && __( 'Copied!' ) }
										{ copyState === 'manual' && `${ __( 'Select all' ) } — Ctrl+C` }
										{ copyState === 'idle' && __( 'Copy all blocks' ) }
									</Button>
								</div>
								<textarea
									ref={ codeRef }
									className="wsg-editor__code-area"
									value={ markup }
									readOnly
									spellCheck={ false }
									onFocus={ ( event ) => event.target.select() }
								/>
							</div>
						) : (
							<div className="wsg-editor__canvas">
								<BlockTools>
									<BlockCanvas height="100%" styles={ settings.styles }>
										<BlockList layout={ rootLayout } />
									</BlockCanvas>
								</BlockTools>
							</div>
						) }

						{ isInspectorOpen && ! isCodeView && (
							<aside className="wsg-editor__inspector">
								<BlockInspector />
							</aside>
						) }
					</div>

					<SaveAsReusable
						endpoint={ config.endpoints?.reusable }
						onCreated={ addReusableBlockVariation }
					/>

					<Popover.Slot />
				</BlockEditorProvider>
			</SlotFillProvider>
		</ShortcutProvider>
	);
}

function initialBlocks( content, fieldSettings ) {
	const parsed = parse( content || '' );

	if ( parsed.length || ! fieldSettings?.template?.length ) {
		return parsed;
	}

	return synchronizeBlocksWithTemplate( [], fieldSettings.template );
}

function buildSettings( config, fieldSettings ) {
	const editor = { ...( config.editor || {} ) };

	if ( fieldSettings?.allowedBlocks?.length ) {
		editor.allowedBlockTypes = [
			...fieldSettings.allowedBlocks,
			...( config.customBlocks || [] ).map( ( block ) => block.name ),
		];
	}

	if ( fieldSettings?.template?.length ) {
		editor.template = fieldSettings.template;
	}

	if ( fieldSettings?.templateLock !== undefined && fieldSettings?.templateLock !== null ) {
		editor.templateLock = fieldSettings.templateLock;
	}

	return {
		...editor,
		mediaUpload: createMediaUploader( config.endpoints?.media ),
		// EditorStyles renders these inside the canvas iframe so it matches the front end.
		// Generated CSS goes last so it can override anything the theme stylesheet sets.
		styles: [
			...( config.styles || [] ).map( ( url ) => ( { css: `@import url("${ url }");` } ) ),
			...( config.inlineStyles ? [ { css: config.inlineStyles } ] : [] ),
		],
		hasFixedToolbar: false,
		isRTL: Boolean( config.translations?.rtl ),
	};
}

/**
 * Replaces the core/block implementation with one that works without WordPress.
 *
 * Upstream's edit component loads the referenced entity through @wordpress/core-data, which
 * expects the WordPress REST API. Here the reference is resolved by PHP instead: the list of
 * saved blocks travels in the editor configuration and the preview is rendered server-side, so
 * the stored markup stays byte-identical to what WordPress writes.
 *
 * The block is filed under a category of its own because the inserter deliberately skips every
 * block in core's "reusable" category — in WordPress those are reached through the Patterns
 * tab, which is part of the editor package this bundle does not ship.
 */
import {
	registerBlockType,
	unregisterBlockType,
	getBlockType,
	registerBlockVariation,
} from '@wordpress/blocks';
import { dispatch, select } from '@wordpress/data';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { Placeholder, SelectControl, PanelBody, Notice } from '@wordpress/components';
import { symbol as symbolIcon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import ServerSidePreview from './ServerSidePreview';

const CATEGORY = 'web-systems-reusable';

export function registerReusableBlocks( config ) {
	const available = config.reusableBlocks || [];
	const endpoints = config.endpoints || {};

	registerCategory();

	if ( getBlockType( 'core/block' ) ) {
		unregisterBlockType( 'core/block' );
	}

	registerBlockType( 'core/block', {
		apiVersion: 3,
		title: __( 'Pattern' ),
		icon: symbolIcon,
		category: CATEGORY,
		description: __( 'Reusable blocks' ),
		attributes: { ref: { type: 'number' } },
		supports: { html: false, reusable: false, inserter: true },
		edit: ( props ) => (
			<ReusableBlockEdit { ...props } available={ available } endpoints={ endpoints } />
		),
		// Dynamic: the reference is expanded by PHP when the document is rendered.
		save: () => null,
	} );

	// One inserter entry per saved block, so editors pick them by name.
	available.forEach( addReusableBlockVariation );
}

/**
 * Adds an inserter entry for a block saved during this session, so it can be reused straight
 * away without reloading the page.
 */
export function addReusableBlockVariation( block ) {
	registerBlockVariation( 'core/block', {
		name: `wsg-ref-${ block.ref }`,
		title: block.title,
		icon: symbolIcon,
		attributes: { ref: block.ref },
		scope: [ 'inserter' ],
	} );
}

function registerCategory() {
	const existing = select( 'core/blocks' ).getCategories();

	if ( existing.some( ( category ) => category.slug === CATEGORY ) ) {
		return;
	}

	dispatch( 'core/blocks' ).setCategories( [
		...existing,
		{ slug: CATEGORY, title: __( 'Reusable blocks' ), icon: symbolIcon },
	] );
}

function ReusableBlockEdit( { attributes, setAttributes, available, endpoints } ) {
	const blockProps = useBlockProps( { className: 'wsg-reusable' } );
	const options = [
		{ label: __( 'Choose a pattern' ), value: '' },
		...available.map( ( block ) => ( { label: block.title, value: String( block.ref ) } ) ),
	];

	const onSelect = ( value ) =>
		setAttributes( { ref: '' === value ? undefined : Number( value ) } );

	if ( ! attributes.ref ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon={ symbolIcon }
					label={ __( 'Pattern' ) }
					instructions={ __( 'Reusable blocks' ) }
				>
					{ available.length ? (
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							hideLabelFromVision
							label={ __( 'Choose a pattern' ) }
							value=""
							options={ options }
							onChange={ onSelect }
						/>
					) : (
						<Notice status="info" isDismissible={ false }>
							{ __( 'No results found.' ) } { __( 'Create pattern' ) } →{ ' ' }
							{ __( 'Settings' ) }
						</Notice>
					) }
				</Placeholder>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Pattern' ) } initialOpen>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Choose a pattern' ) }
						value={ String( attributes.ref ) }
						options={ options }
						onChange={ onSelect }
					/>
				</PanelBody>
			</InspectorControls>

			<ServerSidePreview
				endpoint={ endpoints.preview }
				markup={ `<!-- wp:block {"ref":${ attributes.ref }} /-->` }
			/>
		</div>
	);
}

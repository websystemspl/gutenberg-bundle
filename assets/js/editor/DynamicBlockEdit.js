/**
 * The edit component shared by every PHP-defined block.
 *
 * It builds the inspector panel from the declared field schema, shows the server-rendered
 * output on the canvas, and offers the fields marked "inContent" as quick controls right above
 * the preview while the block is selected.
 */
import { InspectorControls, InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { renderControl } from './controls';
import ServerSidePreview from './ServerSidePreview';

export default function DynamicBlockEdit( { schema, endpoints, attributes, setAttributes, isSelected } ) {
	const blockProps = useBlockProps( { className: 'wsg-block' } );
	const inspectorControls = schema.controls.filter( ( control ) => ! control.inContent );
	const quickControls = schema.controls.filter( ( control ) => control.inContent );

	return (
		<div { ...blockProps }>
			{ inspectorControls.length > 0 && (
				<InspectorControls>
					<PanelBody title={ schema.title } initialOpen>
						{ inspectorControls.map( ( control ) =>
							renderControl( {
								control,
								attributes,
								setAttributes,
								endpoints,
								key: control.name,
							} )
						) }
					</PanelBody>
				</InspectorControls>
			) }

			{ isSelected && quickControls.length > 0 && (
				<div className="wsg-block__quick">
					<p className="wsg-block__quick-title">{ __( 'Content' ) }</p>
					{ quickControls.map( ( control ) =>
						renderControl( {
							control,
							attributes,
							setAttributes,
							endpoints,
							key: control.name,
						} )
					) }
				</div>
			) }

			<ServerSidePreview
				endpoint={ endpoints?.preview }
				name={ schema.name }
				attributes={ attributes }
			/>

			{ schema.innerBlocks && (
				<div className="wsg-block__inner">
					<InnerBlocks
						allowedBlocks={ schema.allowedBlocks?.length ? schema.allowedBlocks : undefined }
					/>
				</div>
			) }
		</div>
	);
}

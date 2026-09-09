/**
 * Maps one PHP-declared field to one editor control.
 *
 * Adding a new control type means adding one entry here and one method on PHP's
 * AttributeBuilder; nothing else in the editor changes.
 */
import {
	TextControl,
	TextareaControl,
	ToggleControl,
	SelectControl,
	RangeControl,
	BaseControl,
} from '@wordpress/components';
import { ColorPalette, RichText } from '@wordpress/block-editor';
import MediaControl from './MediaControl';

export function renderControl( { control, attributes, setAttributes, endpoints, key } ) {
	const value = attributes[ control.name ];
	const onChange = ( next ) => setAttributes( { [ control.name ]: next } );
	const shared = {
		key,
		label: control.label,
		help: control.help,
		__nextHasNoMarginBottom: true,
		__next40pxDefaultSize: true,
	};

	switch ( control.control ) {
		case 'textarea':
		case 'html':
			return (
				<TextareaControl
					{ ...shared }
					value={ value ?? '' }
					placeholder={ control.placeholder }
					onChange={ onChange }
					rows={ control.control === 'html' ? 8 : 4 }
				/>
			);

		case 'richtext':
			return (
				<BaseControl { ...shared } id={ `wsg-rich-${ control.name }` }>
					<div className="wsg-richtext">
						<RichText
							tagName="div"
							value={ value ?? '' }
							placeholder={ control.placeholder }
							onChange={ onChange }
						/>
					</div>
				</BaseControl>
			);

		case 'number':
			return (
				<TextControl
					{ ...shared }
					type="number"
					value={ value ?? '' }
					min={ control.min }
					max={ control.max }
					step={ control.step }
					onChange={ ( next ) => onChange( next === '' ? null : Number( next ) ) }
				/>
			);

		case 'range':
			return (
				<RangeControl
					{ ...shared }
					value={ value ?? control.default ?? 0 }
					min={ control.min ?? 0 }
					max={ control.max ?? 100 }
					step={ control.step ?? 1 }
					onChange={ onChange }
				/>
			);

		case 'toggle':
			return <ToggleControl { ...shared } checked={ !! value } onChange={ onChange } />;

		case 'select':
			return (
				<SelectControl
					{ ...shared }
					value={ value ?? control.default ?? '' }
					options={ control.choices || [] }
					onChange={ onChange }
				/>
			);

		case 'color':
			return (
				<BaseControl { ...shared } id={ `wsg-color-${ control.name }` }>
					<ColorPalette value={ value } onChange={ ( next ) => onChange( next ?? null ) } />
				</BaseControl>
			);

		case 'image':
			return (
				<MediaControl
					key={ key }
					label={ control.label }
					help={ control.help }
					value={ value }
					endpoint={ endpoints?.media }
					onChange={ onChange }
				/>
			);

		case 'url':
			return (
				<BaseControl { ...shared } id={ `wsg-url-${ control.name }` }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ 'URL' }
						type="url"
						value={ value?.url ?? '' }
						onChange={ ( next ) => onChange( { ...( value || {} ), url: next } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ 'Etykieta' }
						value={ value?.label ?? '' }
						onChange={ ( next ) => onChange( { ...( value || {} ), label: next } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ 'Otwórz w nowej karcie' }
						checked={ !! value?.newTab }
						onChange={ ( next ) => onChange( { ...( value || {} ), newTab: next } ) }
					/>
				</BaseControl>
			);

		case 'text':
		default:
			return (
				<TextControl
					{ ...shared }
					value={ value ?? '' }
					placeholder={ control.placeholder }
					onChange={ onChange }
				/>
			);
	}
}

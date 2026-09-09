/**
 * Stands in for @wordpress/vips/worker.
 *
 * The real module carries a ~13 MB WebAssembly build of libvips, used by WordPress to resize
 * and re-encode images in the browser before uploading them. This bundle uploads straight to
 * the server through the `mediaUpload` setting, so that pipeline is never entered — and
 * shipping the binary inside a Composer package that every install downloads is not worth it.
 *
 * Each entry point throws rather than returning something wrong, so if a future upstream change
 * does route through here the failure is loud and traceable.
 */
const unavailable = ( name ) => async () => {
	throw new Error(
		`Client-side image processing (${ name }) is not bundled by web-systems/gutenberg-bundle; ` +
			'uploads are handled by the server instead.'
	);
};

export const vipsCompressImage = unavailable( 'vipsCompressImage' );
export const vipsConvertImageFormat = unavailable( 'vipsConvertImageFormat' );
export const vipsGetUltraHdrInfo = unavailable( 'vipsGetUltraHdrInfo' );
export const vipsHasTransparency = unavailable( 'vipsHasTransparency' );
export const vipsResizeImage = unavailable( 'vipsResizeImage' );
export const vipsRotateImage = unavailable( 'vipsRotateImage' );
export const vipsCancelOperations = async () => {};
export const terminateVipsWorker = () => {};

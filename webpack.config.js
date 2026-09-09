/**
 * Builds the editor bundle from the official @wordpress packages.
 *
 * Two differences from the stock WordPress setup:
 *  - DependencyExtractionWebpackPlugin is removed, because there is no WordPress runtime to
 *    borrow `window.wp.*` from: everything must be bundled.
 *  - Output goes to public/, which `bin/console assets:install` publishes as
 *    public/bundles/websystemsgutenberg/.
 */
const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
	...defaultConfig,
	entry: {
		editor: path.resolve(__dirname, 'assets/js/editor.js'),
		front: path.resolve(__dirname, 'assets/js/front.js'),
		canvas: path.resolve(__dirname, 'assets/js/canvas.js'),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, 'public'),
		filename: '[name].js',
		clean: false,
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve?.alias,
			// Replaces a 13 MB WebAssembly image pipeline this bundle never enters; see the stub.
			'@wordpress/vips/worker': path.resolve(__dirname, 'assets/js/stubs/vips-worker.js'),
		},
	},
	module: {
		...defaultConfig.module,
		rules: [
			// Several @wordpress packages ship strict ESM that imports CommonJS dependencies
			// without a file extension; webpack rejects that unless told not to.
			{ test: /\.m?js$/, resolve: { fullySpecified: false } },
			...defaultConfig.module.rules,
		],
	},
	plugins: defaultConfig.plugins.filter(
		(plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
	),
};

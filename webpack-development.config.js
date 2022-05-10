/**
 * This file was copied from the following and has some adjustments for fitting with this repo.
 * https://github.com/WordPress/gutenberg/blob/%40wordpress/scripts%4022.1.0/tools/webpack/development.js
 *
 * More details of how to enable Fast Refresh in WordPress plugin development can be found in this PR:
 * https://github.com/WordPress/gutenberg/pull/28273
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const sharedConfig = {
	mode: 'development',
	target: defaultConfig.target,
	output: {
		filename: '[name].js',
		path: path.join( __dirname, 'js/build-dev' ),
	},
};

const reactRefreshEntryConfig = {
	...sharedConfig,
	entry: {
		'react-refresh-entry':
			'@pmmmwh/react-refresh-webpack-plugin/client/ReactRefreshEntry.js',
	},
	plugins: [ new DependencyExtractionWebpackPlugin() ],
};

const reactRefreshRuntimeConfig = {
	...sharedConfig,
	entry: {
		'react-refresh-runtime': {
			import: 'react-refresh/runtime.js',
			library: {
				name: 'ReactRefreshRuntime',
				type: 'window',
			},
		},
	},
	plugins: [
		new DependencyExtractionWebpackPlugin( { useDefaults: false } ),
	],
};

module.exports = [ reactRefreshEntryConfig, reactRefreshRuntimeConfig ];

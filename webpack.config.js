const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const path = require( 'path' );

const requestToExternal = ( request ) => {
	// The following default externals are bundled for compatibility with older versions of WP
	// Note CSS for specific components is bundled via admin/assets/src/index.scss
	// WP 5.4 is the min version for <Card* />, <TabPanel />
	const bundled = [
		'@wordpress/compose',
		'@wordpress/components',
		'@wordpress/primitives',
	];
	if ( bundled.includes( request ) ) {
		return false;
	}

	const wcDepMap = {
		'@woocommerce/components': [ 'wc', 'components' ],
		'@woocommerce/navigation': [ 'wc', 'navigation' ],
	};

	return wcDepMap[ request ];
};

const requestToHandle = ( request ) => {
	const wcHandleMap = {
		'@woocommerce/components': 'wc-components',
		'@woocommerce/navigation': 'wc-navigation',
	};

	return wcHandleMap[ request ];
};

const webpackConfig = {
	...defaultConfig,
	resolve: {
		alias: {
			'.~': path.resolve( process.cwd(), 'js/src/' ),
		},
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			requestToExternal,
			requestToHandle,
		} ),
	],
	entry: {
		index: path.resolve( process.cwd(), 'js/src', 'index.js' ),
		'task-complete-setup': path.resolve(
			process.cwd(),
			'js/src/tasks/complete-setup',
			'index.js'
		),
		'custom-inputs': path.resolve(
			process.cwd(),
			'js/src/custom-inputs/select-with-text-input',
			'index.js'
		),
		'product-attributes': path.resolve(
			process.cwd(),
			'js/src/product-attributes',
			'index.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'js/build' ),
	},
};

const sassTest = /\.(sc|sa)ss$/;
const updatedSassOptions = {
	sourceMap: process.env.NODE_ENV === 'production',
	sassOptions: {
		includePaths: [ 'js/src/css/abstracts' ],
	},
	prependData:
		'@import "_colors"; ' +
		'@import "_variables"; ' +
		'@import "_mixins"; ' +
		'@import "_breakpoints"; ',
};

// Update sass-loader config to prepend imports automatically
// like wc-admin, without rebuilding entire Rule config
webpackConfig.module.rules.forEach( ( { test, use }, ruleIndex ) => {
	if ( test.toString() === sassTest.toString() ) {
		use.forEach( ( { loader }, loaderIndex ) => {
			if ( loader === require.resolve( 'sass-loader' ) ) {
				webpackConfig.module.rules[ ruleIndex ].use[
					loaderIndex
				].options = updatedSassOptions;
			}
		} );
	}
} );

module.exports = webpackConfig;

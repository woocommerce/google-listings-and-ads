const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const path = require( 'path' );

const requestToExternal = ( request ) => {
	// Opt-out WordPress packages.
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

	// Opt-in WooCommerce packages.
	// To switch to opt-out, we would have to use '@woocommerce/dependency-extraction-webpack-plugin'.
	const wcDepMap = {
		'@woocommerce/components': [ 'wc', 'components' ],
		'@woocommerce/navigation': [ 'wc', 'navigation' ],
		// Since WooCommerce 5.8, the Settings store no longer contains "countries", "currency" and "adminURL",
		// to be able to fetch that, we use unpublished `'@woocommerce/settings': 'wc-settings` package.
		// It's delivered with WC, so we use Dependency Extraction Webpack Plugin to import it.
		// See https://github.com/woocommerce/woocommerce-admin/issues/7781,
		// https://github.com/woocommerce/woocommerce-admin/issues/7810
		// Please note, that this is NOT https://www.npmjs.com/package/@woocommerce/settings,
		// or https://github.com/woocommerce/woocommerce-admin/tree/main/packages/wc-admin-settings
		// but https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/assets/js/settings/shared/index.ts
		// (at an unknown version).
		'@woocommerce/settings': [ 'wc', 'wcSettings' ],
	};

	return wcDepMap[ request ];
};

const requestToHandle = ( request ) => {
	const wcHandleMap = {
		'@woocommerce/components': 'wc-components',
		'@woocommerce/navigation': 'wc-navigation',
		'@woocommerce/settings': 'wc-settings',
	};

	return wcHandleMap[ request ];
};
const exceptSVGRule = ( rule ) => {
	return ! rule.test.toString().match( /svg/i );
};

const webpackConfig = {
	...defaultConfig,
	module: {
		...defaultConfig.module,
		// Expose image assets as files.
		// In Webpack 5 we would use Asset Modules and `asset/resource`
		rules: [
			// Remove `@wordpress/` rules for SVGs.
			...defaultConfig.module.rules.filter( exceptSVGRule ),
			{
				test: /\.(svg|png|jpe?g|gif)$/i,
				use: {
					loader: 'file-loader',
					options: {
						name: 'images/[path]/[contenthash].[name].[ext]',
					},
				},
				// Prevent Webpack 5 from procesing files again.
				type: 'javascript/auto',
			},
		],
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			'.~': path.resolve( process.cwd(), 'js/src/' ),
		},
		// Resolve jsx/tsx files for `@woocommerce/data`...`/with-plugins-hydration`
		extensions: [ '.ts', '.tsx', '.js', '.jsx', '.json' ],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			combineAssets: false,
			outputFormat: 'json',
			injectPolyfill: true,
			requestToExternal,
			requestToHandle,
			externalizedReportFile: 'externalized.json',
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
			'js/src/custom-inputs',
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

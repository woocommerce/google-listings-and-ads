const webpack = require( 'webpack' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const { hasArgInCLI } = require( '@wordpress/scripts/utils' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const ReactRefreshWebpackPlugin = require( '@pmmmwh/react-refresh-webpack-plugin' );
const path = require( 'path' );

const isProduction = process.env.NODE_ENV === 'production';
const hasReactFastRefresh = hasArgInCLI( '--hot' ) && ! isProduction;

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
		'@woocommerce/data': [ 'wc', 'data' ],
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
		'@woocommerce/customer-effort-score': [ 'wc', 'customerEffortScore' ],
	};

	return wcDepMap[ request ];
};

const requestToHandle = ( request ) => {
	const wcHandleMap = {
		'@woocommerce/components': 'wc-components',
		'@woocommerce/navigation': 'wc-navigation',
		'@woocommerce/data': 'wc-store-data',
		'@woocommerce/settings': 'wc-settings',
		'@woocommerce/customer-effort-score': 'wc-customer-effort-score',
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
		fallback: {
			/**
			 * Automatic polyfills for native node.js modules were removed from webpack v5.
			 * And `postcss` requires the `path` module, so here needs a polyfill.
			 */
			path: require.resolve( 'path-browserify' ),
		},
	},
	plugins: [
		...defaultConfig.plugins.filter( ( plugin ) => {
			const filteredPlugins = [
				'DependencyExtractionWebpackPlugin',
				'CopyPlugin',
				'ReactRefreshPlugin',
			];
			return ! filteredPlugins.includes( plugin.constructor.name );
		} ),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			externalizedReport:
				! hasReactFastRefresh && '../../.externalized.json',
			requestToExternal,
			requestToHandle,
		} ),
		/**
		 * Automatic polyfills for native node.js modules were removed from webpack v5.
		 * And `@wordpress/components` below version 16.x depends on `@wordpress/compose`,
		 * which directly accesses `process.env`.
		 * Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/compose/src/hooks/use-reduced-motion/index.js#L21
		 *
		 * So the fallback is required here.
		 * It may be possible to remove this fallback
		 * when `@wordpress/components` is upgraded above 17.0.0+,
		 * or when it is removed from the `requestToExternal` function above.
		 */
		new webpack.ProvidePlugin( {
			process: 'process/browser',
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

if ( hasReactFastRefresh ) {
	/**
	 * If the development environment uses HTTPS,
	 * it will fail when first connecting to webpack dev server,
	 * so turn off the `overlay` option here.
	 * Ref: https://github.com/pmmmwh/react-refresh-webpack-plugin/blob/v0.5.4/docs/TROUBLESHOOTING.md#component-not-updating-with-bundle-splitting-techniques
	 */
	webpackConfig.plugins.push(
		new ReactRefreshWebpackPlugin( { overlay: false } )
	);

	webpackConfig.optimization = {
		...webpackConfig.optimization,
		// With multiple entries, it will need a webpack runtime to be shared
		// for all generated chunks when enabling Fast Refresh.
		runtimeChunk: 'single',
	};
}

const sassTest = /\.(sc|sa)ss$/;
const updatedSassOptions = {
	sourceMap: process.env.NODE_ENV === 'production',
	sassOptions: {
		includePaths: [ 'js/src/css/abstracts' ],
	},
	additionalData:
		'@use "sass:color";' +
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

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const { hasArgInCLI } = require( '@wordpress/scripts/utils' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );

const ReactRefreshWebpackPlugin = require( '@pmmmwh/react-refresh-webpack-plugin' );
const path = require( 'path' );

const isProduction = process.env.NODE_ENV === 'production';
const hasReactFastRefresh = hasArgInCLI( '--hot' ) && ! isProduction;

const exceptSVGAndPNGRule = ( rule ) => {
	return ! rule.test.toString().match( /svg|png/i );
};

const webpackConfig = {
	...defaultConfig,
	module: {
		...defaultConfig.module,
		// Expose image assets as files.
		rules: [
			// Remove `@wordpress/` rules for SVGs.
			...defaultConfig.module.rules.filter( exceptSVGAndPNGRule ),
			{
				test: /\.(svg|png|jpe?g|gif)$/i,
				type: 'asset/resource',
				generator: {
					filename: 'images/[path]/[contenthash].[name][ext]',
				},
			},
			// Fix module not found problem for `process/browser`.
			// Ref: https://webpack.js.org/configuration/module/#resolvefullyspecified
			{
				test: /\.m?js$/,
				resolve: { fullySpecified: false },
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
				// Filter WP/DEWP, as we will replace it with WC one.
				'DependencyExtractionWebpackPlugin',
				/**
				 * We don't use block.json to build the client files.
				 * And CopyPlugin will cause any changes to files in the '<rootDir>/src' folder
				 * to trigger an unwanted webpack rebuild.
				 *
				 * Ref:
				 * - https://github.com/WordPress/gutenberg/tree/%40wordpress/scripts%4022.1.0/packages/scripts#default-webpack-config
				 * - https://github.com/WordPress/gutenberg/blob/%40wordpress/scripts%4022.1.0/packages/scripts/config/webpack.config.js#L232-L240
				 */
				'CopyPlugin',
				'MiniCssExtractPlugin',
				'ReactRefreshPlugin',
			];
			return ! filteredPlugins.includes( plugin.constructor.name );
		} ),
		new WooCommerceDependencyExtractionWebpackPlugin( {
			externalizedReport:
				! hasReactFastRefresh && '../../.externalized.json',
		} ),
		new MiniCSSExtractPlugin( {
			filename: '[name].css',
			chunkFilename: '[name].css?ver=[chunkhash]',
		} ),
	],
	entry: {
		index: path.resolve( process.cwd(), 'js/src', 'index.js' ),
		'product-attributes': path.resolve(
			process.cwd(),
			'js/src/product-attributes',
			'index.js'
		),
		'gtag-events': path.resolve(
			process.cwd(),
			'js/src/gtag-events',
			'index.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'js/build' ),
		chunkFilename: '[name].js?ver=[chunkhash]',
	},
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...defaultConfig.optimization.splitChunks,
			cacheGroups: {
				...defaultConfig.optimization.splitChunks.cacheGroups,
				vendors: {
					name: 'vendors',
					test: /([\\/])node_modules\1/,
				},
				commons: {
					name: 'commons',
					test( { resource } ) {
						return /([\\/])js\1src\1(components|data|hooks|images|utils|wcdl)\1/.test(
							resource
						);
					},
				},
			},
		},
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
	sourceMap: ! isProduction,
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

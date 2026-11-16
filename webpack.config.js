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
				test: /\.svg$/i,
				oneOf: [
					{
						resourceQuery: /inline/,
						issuer: /\.[jt]sx?$/,
						use: [
							{
								loader: require.resolve( '@svgr/webpack' ),
								options: {
									svgoConfig: {
										plugins: [
											{
												name: 'preset-default',
												params: {
													overrides: {
														removeViewBox: false,
													},
												},
											},
											{
												name: 'prefixIds',
												params: {
													prefix: true,
												},
											},
										],
									},
									exportType: 'default',
								},
							},
						],
					},
					// Default: emit SVG as a file
					{
						type: 'asset/resource',
						generator: {
							filename: 'images/[path][contenthash].[name][ext]',
						},
					},
				],
			},
			{
				test: /\.(png|jpe?g|gif)$/i,
				type: 'asset/resource',
				generator: {
					filename: 'images/[path][contenthash].[name][ext]',
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
			'~': path.join( __dirname, 'js/src' ),
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
				'MiniCssExtractPlugin',
				'ReactRefreshPlugin',
			];
			return ! filteredPlugins.includes( plugin.constructor.name );
		} ),
		new WooCommerceDependencyExtractionWebpackPlugin( {
			externalizedReport:
				! hasReactFastRefresh && '../../.externalized.json',
			requestToExternal: ( request ) => {
				if ( request.startsWith( '@wordpress/dataviews' ) ) {
					return null;
				}

				// Shared, plugin-agnostic notification slot — loaded via its own
				// script handle so any WooCommerce extension can depend on it
				// instead of bundling a second copy.
				if (
					request ===
					'~/notifications-system/woo-marketing-notifications-slot'
				) {
					return 'wooMarketingNotificationsSlot';
				}
			},
			requestToHandle: ( request ) => {
				if (
					request ===
					'~/notifications-system/woo-marketing-notifications-slot'
				) {
					return 'woocommerce-marketing-notifications-system-slot';
				}
			},
		} ),
		new MiniCSSExtractPlugin( {
			filename: '[name].css',
			chunkFilename: '[name].css?ver=[chunkhash]',
		} ),
	],
	entry: () => ( {
		'wp-dataviews-shim': path.resolve(
			process.cwd(),
			'js/src/shims',
			'wp-dataviews.js'
		),
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
		'wp-consent-api': path.resolve(
			process.cwd(),
			'js/src/wp-consent-api',
			'index.js'
		),
		'notification-manager': path.resolve(
			process.cwd(),
			'js/src/notification-manager',
			'index.js'
		),
		'notifications-system': path.resolve(
			process.cwd(),
			'js/src/notifications-system',
			'index.js'
		),
		'order-attribution': path.resolve(
			process.cwd(),
			'js/src/meta-boxes/order-attribution',
			'index.js'
		),
		'channel-visibility-meta-box': path.join(
			__dirname,
			'js/src/meta-boxes/channel-visibility',
			'index.js'
		),
		'woo-marketing-notifications-slot': {
			import: path.resolve(
				process.cwd(),
				'js/src/notifications-system/woo-marketing-notifications-slot',
				'index.js'
			),
			// Exposes the module's exports on `window.wooMarketingNotificationsSlot`
			// so other bundles (e.g. notifications-system.js) can depend on the
			// registered script handle instead of re-bundling this code.
			library: {
				type: 'window',
				name: 'wooMarketingNotificationsSlot',
			},
		},
	} ),
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'js/build' ),
		chunkFilename: '[name].js?ver=[chunkhash]',
		// Required for the `woo-marketing-notifications-slot` entry's `library: { type: 'window' }'.
		enabledLibraryTypes: [ 'window' ],
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
					minChunks: 2,
					test( { resource } ) {
						return /([\\/])js\1src\1(components|data|hooks|images|utils)\1/.test(
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
		loadPaths: [ path.resolve( __dirname, 'js/src/css/abstracts' ) ],
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

// Give every WordPress Design System token (`var(--wpds-*)`) in our styles its
// fallback value at build time, so styles still work where no token stylesheet is
// loaded. Stylelint's `plugin-wpds/no-token-fallback-values` rule rejects fallbacks
// written by hand. The wp-scripts CSS, PCSS and SCSS rules share one PostCSS options
// object, so the plugin is added once and covers all three.
// Ref: https://github.com/WordPress/gutenberg/tree/trunk/packages/theme-build-tools
const {
	default: dsTokenFallbacks,
} = require( '@wordpress/theme-build-tools/postcss-plugins/postcss-ds-token-fallbacks' );

webpackConfig.module.rules.forEach( ( { use } ) => {
	if ( ! Array.isArray( use ) ) {
		return;
	}
	use.forEach( ( { loader, options } ) => {
		const plugins = options?.postcssOptions?.plugins;
		if (
			/[\\/]postcss-loader[\\/]/.test( loader ) &&
			Array.isArray( plugins ) &&
			! plugins.some(
				( plugin ) =>
					plugin.postcssPlugin === 'postcss-ds-token-fallbacks'
			)
		) {
			// Replace the array rather than mutating it: in development builds it is
			// the `@wordpress/postcss-plugins-preset` module's own export.
			options.postcssOptions.plugins = [ dsTokenFallbacks(), ...plugins ];
		}
	} );
} );

module.exports = webpackConfig;

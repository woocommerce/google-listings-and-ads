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

const newRules = defaultRules = defaultConfig.module.rules;
const sassRules = defaultRules.length - 1;
const sassLoader = defaultConfig.module.rules[sassRules].use.length - 1;

newRules[sassRules].use[sassLoader].options.sassOptions = {
	includePaths: [
		'js/src/css/abstracts',
	],
};

newRules[sassRules].use[sassLoader].options.prependData =
	'@import "_colors"; ' +
	'@import "_variables"; ' +
	'@import "_mixins"; ' +
	'@import "_breakpoints"; ';


const webpackConfig = {
	...defaultConfig,
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
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'js/build' ),
	},
	module: {
		...defaultConfig.module,
		rules: newRules,
	},
};

module.exports = webpackConfig;

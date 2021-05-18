// Import WP-scripts presets to extend them,
// see https://developer.wordpress.org/block-editor/packages/packages-scripts/#advanced-information-11.
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	// Workaround https://github.com/woocommerce/woocommerce-admin/issues/6483.
	transformIgnorePatterns: [ 'node_modules/(?!@woocommerce/date/build)' ],
	transform: {
		...( defaultConfig.transform || [] ),
		'node_modules/@woocommerce/date/build/index.js': 'babel-jest',
	},
	moduleNameMapper: {
		// Transform our `.~/` alias.
		'\\.~/(.*)$': '<rootDir>/js/src/$1',
	},
};

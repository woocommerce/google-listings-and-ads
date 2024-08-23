// Import WP-scripts presets to extend them,
// see https://developer.wordpress.org/block-editor/packages/packages-scripts/#advanced-information-11.
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	testEnvironment: 'jsdom',
	setupFiles: [ 'core-js', '<rootDir>/js/src/tests/jest-unit.setup.js' ],
	transformIgnorePatterns: [
		// Fix that `is-plain-obj@4.1.0` doesn't provide the CommonJS build, so it needs to be transformed.
		'<rootDir>/node_modules/(?!@woocommerce/components/node_modules/is-plain-obj/)',
	],
	moduleNameMapper: {
		'\\.(png|jpg)$': '<rootDir>/tests/mocks/assets/imageMock.js',
		'\\.svg$': '<rootDir>/tests/mocks/assets/svgrMock.js',
		'\\.scss$': '<rootDir>/tests/mocks/assets/styleMock.js',
		// Transform our `.~/` alias.
		'^\\.~/(.*)$': '<rootDir>/js/src/$1',
		'@woocommerce/settings':
			'<rootDir>/js/src/tests/dependencies/woocommerce/settings',
		'@automattic/calypso-config':
			'<rootDir>/js/src/tests/dependencies/automattic/calypso-config',
		// Ignore that 'qrcode.react' module is incorrectly listed in dev dependencies of '@automattic/components'.
		// Ref: https://github.com/Automattic/wp-calypso/blob/%40automattic/components%402.1.1/packages/components/package.json#L72
		'@automattic/components':
			'<rootDir>/js/src/tests/dependencies/automattic/components',
		// Fix `@woocommerce/components` still using incompatible `@woocommerce/currency`.
		'@woocommerce/currency': require.resolve( '@woocommerce/currency' ),
		// Fix the React versioning conflicts between @wordpress/* and @woocommerce/*.
		// It should be removed after they don't have versioning conflicts.
		'^react$': require.resolve( 'react' ),
		// Force 'uuid' to resolve with the CommonJS entry point, because jest doesn't
		// support `package.json.exports`.
		'^uuid$': require.resolve( 'uuid' ),
	},
	// Exclude e2e tests from unit testing.
	testPathIgnorePatterns: [
		'/node_modules/',
		'/__helpers__/',
		'<rootDir>/tests/e2e/',
	],
	coveragePathIgnorePatterns: [
		'/node_modules/',
		'/__helpers__/',
		'<rootDir>/tests/',
	],
	watchPathIgnorePatterns: [
		'<rootDir>/.externalized.json',
		'<rootDir>/js/build/',
		'<rootDir>/js/build-dev',
	],
	globals: {
		wcAdminFeatures: {
			navigation: false,
		},
		wcSettings: {
			currency: {
				code: 'USD',
				precision: 2,
				symbol: '$',
				symbolPosition: 'left',
				decimalSeparator: '.',
				priceFormat: '%1$s%2$s',
				thousandSeparator: ',',
			},
		},
	},
};

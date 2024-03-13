// Import WP-scripts presets to extend them,
// see https://developer.wordpress.org/block-editor/packages/packages-scripts/#advanced-information-11.
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	testEnvironment: 'jsdom',
	setupFiles: [ 'core-js', '<rootDir>/js/src/tests/jest-unit.setup.js' ],
	transformIgnorePatterns: [
		// Fix that `is-plain-obj@4.1.0` doesn't provide the CommonJS build, so it needs to be transformed.
		'<rootDir>/node_modules/(?!is-plain-obj/)',
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
		glaData: {
			slug: 'gla',
			mcSetupComplete: true,
			mcSupportedCountry: true,
			mcSupportedLanguage: true,
			adsSetupComplete: true,
			adsConnected: true,
			enableReports: true,
			dateFormat: 'F j, Y',
			timeFormat: 'g:i a',
			initialWpData: {
				version: '1.2.3',
			},
		},
	},
	timers: 'fake',
};

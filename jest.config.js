// Import WP-scripts presets to extend them,
// see https://developer.wordpress.org/block-editor/packages/packages-scripts/#advanced-information-11.
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	testEnvironment: 'jsdom',
	setupFiles: [ 'core-js', '<rootDir>/js/src/tests/jest-unit.setup.js' ],
	moduleNameMapper: {
		'\\.png$': '<rootDir>/tests/mocks/assets/imageMock.js',
		'\\.svg$': '<rootDir>/tests/mocks/assets/svgrMock.js',
		'\\.scss$': '<rootDir>/tests/mocks/assets/styleMock.js',
		// Transform our `.~/` alias.
		'^\\.~/(.*)$': '<rootDir>/js/src/$1',
		'^extracted/(.*)$': '$1',
		'@woocommerce/settings':
			'<rootDir>/js/src/tests/dependencies/woocommerce/settings',
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
		glaData: {
			mcSetupComplete: true,
			mcSupportedCountry: true,
			mcSupportedLanguage: true,
			adsSetupComplete: true,
			enableReports: true,
			dateFormat: 'F j, Y',
			timeFormat: 'g:i a',
		},
	},
};

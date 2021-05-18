// Import WP-scripts presets to extend them,
// see https://developer.wordpress.org/block-editor/packages/packages-scripts/#advanced-information-11.
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	moduleNameMapper: {
		// Transform our `.~/` alias.
		'\\.~/(.*)$': '<rootDir>/js/src/$1',
	},
	// Exclude e2e tests from unit testing.
	testPathIgnorePatterns: [ '/node_modules/', '/tests/e2e/' ],
};

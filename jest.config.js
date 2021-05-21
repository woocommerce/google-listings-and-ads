// Import WP-scripts presets to extend them,
// see https://developer.wordpress.org/block-editor/packages/packages-scripts/#advanced-information-11.
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

const wcPackagesNeedTransform = [
	'components',
	'components/node_modules/@woocommerce/navigation',
	'currency',
	'data',
	'date',
	'navigation',
	'number',
	'tracks',
].join( '|' );

module.exports = {
	...defaultConfig,
	// Workaround https://github.com/woocommerce/woocommerce-admin/issues/6483.
	transformIgnorePatterns: [
		`<rootDir>/node_modules/(?!@woocommerce/(${ wcPackagesNeedTransform })/build/)`,
	],
	moduleNameMapper: {
		// Transform our `.~/` alias.
		'\\.svg$': '<rootDir>/tests/mocks/assets/svgrMock.js',
		'\\.scss$': '<rootDir>/tests/mocks/assets/styleMock.js',
		'\\.~/(.*)$': '<rootDir>/js/src/$1',
	},
	// Exclude e2e tests from unit testing.
	testPathIgnorePatterns: [ '/node_modules/', '/tests/e2e/' ],
};

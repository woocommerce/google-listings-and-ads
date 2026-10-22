// `@wordpress/scripts` no longer ships a Jest config, so this uses the published preset
// with a Babel transform instead, as its Jest upgrade guide suggests.
// Ref: https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/docs/vitest-migration.md#keep-an-existing-jest-suite
const babelTransform = [
	'babel-jest',
	{ presets: [ '@wordpress/babel-preset-default' ] },
];

module.exports = {
	preset: '@wordpress/jest-preset-default',
	testEnvironment: 'jsdom',
	setupFiles: [ 'core-js', '<rootDir>/js/src/tests/jest-unit.setup.js' ],
	transformIgnorePatterns: [
		// Fix that `is-plain-obj@4.1.0` doesn't provide the CommonJS build, so it needs to be transformed.
		// Matches every nested copy (e.g. under @woocommerce/components or @wordpress/core-data), not just one.
		// `@wordpress/theme` (pulled in transitively via @wordpress/preferences -> @wordpress/ui) is ESM-only
		// (no CJS build at all), so it needs to be transformed too.
		// `parsel-js` (pulled in via @wordpress/block-editor under @woocommerce/components) and `marked`
		// (pulled in via @wordpress/blocks) are ESM-only too.
		'<rootDir>/node_modules/(?!.*/node_modules/is-plain-obj/|d3-.*/|internmap/|@wordpress/theme/|parsel-js/|(?:.*/)?marked/)',
	],
	transform: {
		'\\.[jt]sx?$': babelTransform,
		// `transformIgnorePatterns` above lets `@wordpress/theme`'s `.mjs` file through, but
		// the default transform only matches `.js/.jsx/.ts/.tsx`, so it still needs its own
		// entry here or it reaches Jest untransformed and crashes on the `import` statement.
		'\\.mjs$': babelTransform,
	},
	moduleNameMapper: {
		'\\.(png|jpg)$': '<rootDir>/tests/mocks/assets/imageMock.js',
		'\\.svg\\?inline$': '<rootDir>/tests/mocks/assets/svgrMock.js',
		'\\.svg$': '<rootDir>/tests/mocks/assets/svgFileMock.js',
		'\\.scss$': '<rootDir>/tests/mocks/assets/styleMock.js',
		// Transform our `~/` alias.
		'^~/(.*)$': '<rootDir>/js/src/$1',
		// Ignore known `@wordpress/components` deprecation notices in every copy of `@wordpress/deprecated`.
		'^@wordpress/deprecated$':
			'<rootDir>/js/src/tests/dependencies/wordpress/deprecated',
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

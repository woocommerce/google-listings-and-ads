const webpackConfig = require( './webpack.config' );

const webpackResolver = {
	config: {
		resolve: {
			...webpackConfig.resolve,
			/**
			 * Make eslint correctly resolve files that omit the .js extensions.
			 * The default value `'...'` doesn't work before the current eslint support for webpack v5.
			 * Ref: https://webpack.js.org/configuration/resolve/#resolveextensions
			 */
			extensions: [ '.js' ],
		},
	},
};

module.exports = {
	extends: [
		'plugin:@woocommerce/eslint-plugin/recommended',
		'plugin:import/recommended',
	],
	settings: {
		jsdoc: {
			mode: 'typescript',
		},
		'import/core-modules': [
			'webpack',
			'stylelint',
			'@woocommerce/product-editor',
			'@woocommerce/block-templates',
			'@wordpress/stylelint-config',
			'@pmmmwh/react-refresh-webpack-plugin',
			'react-transition-group',
			'jquery',
		],
		'import/resolver': { webpack: webpackResolver },
	},
	globals: {
		getComputedStyle: 'readonly',
	},
	rules: {
		'@wordpress/no-unsafe-wp-apis': 1,
		'react-hooks/exhaustive-deps': [
			'warn',
			{
				additionalHooks: 'useSelect',
			},
		],
		'jest/expect-expect': [
			'warn',
			{ assertFunctionNames: [ 'expect', 'expect[A-Z]\\w*' ] },
		],
	},
	overrides: [
		{
			files: [ 'tests/e2e/**/*.js' ],
			rules: {
				'jest/no-done-callback': [ 'off' ],
			},
		},
	],
};

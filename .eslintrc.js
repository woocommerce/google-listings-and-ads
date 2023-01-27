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
			'@wordpress/stylelint-config',
			'@pmmmwh/react-refresh-webpack-plugin',
			'react-transition-group',
		],
		'import/resolver': { webpack: webpackResolver },
	},
	rules: {
		'@wordpress/no-unsafe-wp-apis': 1,
		'react-hooks/exhaustive-deps': [
			'warn',
			{
				additionalHooks: 'useSelect',
			},
		],
		// When a file imports from both `@wordpress/components` and `extracted/@wordpress/components`
		// at the same time, it would get the "no-duplicates" warning. It should be considered a
		// false positive when working on the externalization process. So here we temporarily change
		// to use "no-duplicate-imports" instead.
		//
		// TODO: After the externalizations of `@wordpress/*` and `@woocommerce/*` are complete,
		//       remove the following two lines of rule settings.
		'import/no-duplicates': 'off',
		'no-duplicate-imports': 'warn',
	},
};

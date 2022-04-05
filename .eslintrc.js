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
			/**
			 * Make eslint be able to resolve the exports config of `use-debounce`.
			 * The `exports` config of package.json doesn't work before the current eslint support it.
			 * Ref: https://github.com/xnimorz/use-debounce/blob/5.2.0/package.json#L8-L14
			 */
			conditionNames: [ 'import', 'require' ],
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
	},
};

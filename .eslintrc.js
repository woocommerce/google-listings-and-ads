module.exports = {
	extends: [
		'plugin:@woocommerce/eslint-plugin/recommended',
		'plugin:import/recommended',
	],
	settings: {
		jsdoc: {
			mode: 'typescript',
		},
		'import/core-modules': [ 'webpack' ],
		'import/resolver': 'webpack',
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

const jsdocConfig = require( '@wordpress/eslint-plugin/configs/jsdoc' );
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
		'@wordpress/i18n-text-domain': [
			'error',
			{ allowedTextDomain: 'google-listings-and-ads' },
		],
		'@wordpress/no-unsafe-wp-apis': 1,
		'react/react-in-jsx-scope': 'off',
		'react-hooks/exhaustive-deps': [
			'warn',
			{
				additionalHooks: 'useSelect',
			},
		],
		// compatibility-code "WC < 7.6"
		//
		// Turn it off because:
		// - `import { CurrencyFactory } from '@woocommerce/currency';`
		//   It's supported only since WC 7.6.0
		// - `import { userEvent } from '@testing-library/user-event';`
		//   It works but the official documentation also recommends using the default export
		'import/no-named-as-default': 'off',
		'jest/expect-expect': [
			'warn',
			{ assertFunctionNames: [ 'expect', 'expect[A-Z]\\w*' ] },
		],
		// Turn it off temporarily because it involves a lot of re-alignment. We can revisit it later.
		'jsdoc/check-line-alignment': 'off',
		// The JS package `tracking-jsdoc` changes the definition of the `@fires` tag.
		// List shared `@event` names to avoid false alarms.
		'jsdoc/no-undefined-types': [
			'error',
			{
				definedTypes: [
					...jsdocConfig.rules[ 'jsdoc/no-undefined-types' ][ 1 ]
						.definedTypes,
					'gla_datepicker_update',
					'gla_documentation_link_click',
					'gla_faq',
					'gla_filter',
					'gla_google_account_connect_button_click',
					'gla_google_mc_link_click',
					'gla_launch_paid_campaign_button_click',
					'gla_mc_account_switch_account_button_click',
					'gla_modal_closed',
					'gla_modal_open',
					'gla_paid_campaign_step',
					'gla_setup_ads',
					'gla_setup_mc',
					'gla_table_go_to_page',
					'gla_table_page_click',
				],
			},
		],
	},
	overrides: [
		{
			files: [ 'js/src/external-components/woocommerce/**' ],
			rules: {
				'@wordpress/i18n-text-domain': [
					'error',
					{ allowedTextDomain: 'woocommerce' },
				],
			},
		},
		{
			files: [ 'js/src/external-components/wordpress/**' ],
			rules: {
				'@wordpress/i18n-text-domain': [
					'error',
					{ allowedTextDomain: '' },
				],
			},
		},
		{
			files: [ 'tests/e2e/**/*.js' ],
			rules: {
				'jest/no-done-callback': [ 'off' ],
			},
		},
	],
};

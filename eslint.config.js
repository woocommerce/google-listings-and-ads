/**
 * External dependencies
 */
const woocommerce = require( '@woocommerce/eslint-plugin' );
const wordpress = require( '@wordpress/eslint-plugin' );
const importPlugin = require( 'eslint-plugin-import' );

/**
 * Internal dependencies
 */
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

// `jsdoc/no-undefined-types` is configured as `[ severity, options ]` in the flat config array.
const { definedTypes } = wordpress.configs.jsdoc
	.flatMap( ( config ) => config.rules?.[ 'jsdoc/no-undefined-types' ] ?? [] )
	.find( ( value ) => value?.definedTypes );

module.exports = [
	{
		ignores: [
			'**/build/**',
			'**/build-dev/**',
			'**/build-module/**',
			'coverage/**',
			'languages/**',
			'**/node_modules/**',
			'vendor/**',
			'legacy/**',
			'tests/e2e/test-results/**',
		],
	},
	{
		linterOptions: {
			// Keep ESLint 8's behavior, which didn't report unused disable directives.
			reportUnusedDisableDirectives: 'off',
		},
	},
	...woocommerce.configs.recommended,
	{
		// The `import` plugin is already registered (wrapped for ESLint 10) by the
		// config above, so only its recommended rules are enabled here.
		rules: importPlugin.flatConfigs.recommended.rules,
	},
	{
		languageOptions: {
			globals: {
				getComputedStyle: 'readonly',
				navigator: 'readonly',
				MutationObserver: 'readonly',
			},
		},
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
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'google-listings-and-ads' },
			],
			'@wordpress/no-unsafe-wp-apis': 1,
			// Keep ESLint 8's behavior, which didn't check unused `catch` bindings.
			'@typescript-eslint/no-unused-vars': [
				'error',
				{
					varsIgnorePattern: 'createElement',
					ignoreRestSiblings: true,
					caughtErrors: 'none',
				},
			],
			// Temporary conversion to warnings, as fixing them changes the translatable
			// strings and drops their existing translations. They will be handled separately.
			'@wordpress/i18n-no-flanking-whitespace': 'warn',
			'@wordpress/i18n-translator-comments': 'warn',
			'@wordpress/i18n-hyphenated-range': 'warn',
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
			// Turn it off temporarily because it involves a lot of re-alignment. We can revisit it later.
			'jsdoc/check-line-alignment': 'off',
			// Originally, `@fires` tag indicates that when a method is called, it fires
			// a specified type of event that can be listened to, e.g. a native `CustomEvent`.
			// The JS package `tracking-jsdoc` changes the definition of the `@fires` tag to
			// be able to indicate a tracking event will be sent. Therefore, here we list
			// shared `@event` names to avoid false alarms.
			'jsdoc/no-undefined-types': [
				'error',
				{
					definedTypes: [
						...definedTypes,
						'JSX',
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
						'gla_setup_ads_only',
						'gla_table_go_to_page',
						'gla_table_page_click',
					],
				},
			],
		},
	},
	{
		files: [ '**/*.js', '**/*.cjs' ],
		rules: {
			// The `@typescript-eslint/no-var-requires` rule turned off for JS files by
			// `@woocommerce/eslint-plugin` has been replaced by this one.
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
	{
		files: [ '**/__mocks__/**/*.js' ],
		languageOptions: {
			globals: {
				jest: 'readonly',
			},
		},
	},
	{
		files: [
			'**/@(test|__tests__)/**/*.[jt]s?(x)',
			'**/?(*.)test.[jt]s?(x)',
			'**/tests/**/*.[jt]s?(x)',
		],
		rules: {
			'jest/expect-expect': [
				'warn',
				{ assertFunctionNames: [ 'expect', 'expect[A-Z]\\w*' ] },
			],
		},
	},
	{
		files: [ 'js/src/components/external/woocommerce/**' ],
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'woocommerce' },
			],
		},
	},
	{
		files: [ 'js/src/components/external/wordpress/**' ],
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
];

/**
 * External dependencies
 **/
const path = require( 'path' );
// eslint-disable-next-line import/no-extraneous-dependencies
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );

const cssLoaders = [
	{
		loader: MiniCSSExtractPlugin.loader,
	},
	{
		loader: require.resolve( 'css-loader' ),
		options: {
			sourceMap: process.env.NODE_ENV !== 'production',
			modules: {
				auto: true,
			},
		},
	},
];

module.exports = {
	core: {
		builder: 'webpack5',
	},
	stories: [
		// any file inside a stories folder in the JS dir
		'../js/src/**/stories/*.@(js|jsx|ts|tsx)',
	],
	addons: [
		'@storybook/addon-links',
		'@storybook/addon-essentials',
		'@storybook/addon-interactions',
		'@storybook/addon-docs',
	],

	webpackFinal: async ( config, {} ) => {
		config.resolve.modules = [
			path.resolve( __dirname, '..' ),
			'node_modules',
		];
		config.resolve.alias = {
			...config.resolve.alias,
			'.~': path.resolve( __dirname, '../js/src' ),
		};

		config.module.rules = [
			...config.module.rules,
			...[
				{
					test: /\.(sc|sa)ss$/,
					use: [
						...cssLoaders,
						{
							loader: require.resolve( 'sass-loader' ),
							options: {
								sourceMap:
									process.env.NODE_ENV === 'production',
								additionalData:
									'@import "@wordpress/base-styles/_colors"; ' +
									'@import "@wordpress/base-styles/_variables"; ' +
									'@import "@wordpress/base-styles/_mixins"; ' +
									'@import "@wordpress/base-styles/_breakpoints"; ',
							},
						},
					],
				},
			],
		];

		config.plugins.push(
			new MiniCSSExtractPlugin( {
				filename: `[name].css`,
			} )
		);

		return config;
	},
};

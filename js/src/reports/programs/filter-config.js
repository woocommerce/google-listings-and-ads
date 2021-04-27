/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { getProgramLabels } from './mocked-programs-data';

export const programsFilterConfig = {
	label: __( 'Show', 'google-listings-and-ads' ),
	staticParams: [ 'period' ],
	param: 'programs',
	filters: [
		{
			label: __( 'All Google programs', 'google-listings-and-ads' ),
			value: 'all',
		},
		{
			label: __( 'Single program', 'google-listings-and-ads' ),
			value: 'single',
			subFilters: [
				{
					component: 'Search',
					value: 'single_program',
					path: [ 'select_program' ],
					settings: {
						// TODO: setup config once following issues are fixed:
						// https://github.com/woocommerce/woocommerce-admin/issues/6061
						// https://github.com/woocommerce/woocommerce-admin/issues/6062
						type: 'custom_is_broken',
						param: 'custom_programs',
						getLabels: getProgramLabels,
						labels: {
							placeholder: __(
								'Type to search for a program',
								'google-listings-and-ads'
							),
							button: __(
								'Single Program',
								'google-listings-and-ads'
							),
						},
						autocompleter: {
							name: 'fruit',
							// The prefix that triggers this completer
							triggerPrefix: '~',
							// According to https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#usage
							// this could be an Object,
							// but wc-admin#Search https://woocommerce.github.io/woocommerce-admin/#/components/packages/search/README?id=search
							// requires a function
							options: [
								{ visual: '🍎', name: 'Apple', id: 1 },
								{ visual: '🍊', name: 'Orange', id: 2 },
								{ visual: '🍇', name: 'Grapes', id: 3 },
							],
							// Returns a label for an option like "🍊 Orange"
							getOptionLabel: ( option ) => (
								<span>
									<span className="icon">
										{ option.visual }
									</span>
									{ option.name }
								</span>
							),
							// Declares that options should be matched by their name
							getOptionKeywords: ( option ) => [ option.name ],
							// Declares that the Grapes option is disabled
							isOptionDisabled: ( option ) =>
								option.name === 'Grapes',
							// Declares completions should be inserted as abbreviations
							getOptionCompletion: ( option ) => (
								<abbr title={ option.name }>
									{ option.visual }
								</abbr>
							),
						},
					},
				},
			],
		},
		{
			label: __( 'Comparison', 'google-listings-and-ads' ),
			value: 'comparison',
		},
	],
};

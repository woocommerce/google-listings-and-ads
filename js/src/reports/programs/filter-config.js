/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getIdsFromQuery } from '../utils';
import { FREE_LISTINGS_PROGRAM_ID, REPORT_PROGRAM_PARAM } from '.~/constants';

export const programsFilterConfig = ( adsCampaigns ) => {
	if ( ! adsCampaigns ) {
		adsCampaigns = [];
	}
	const programsList = [
		{
			id: FREE_LISTINGS_PROGRAM_ID,
			name: __( 'Free Listings', 'google-listings-and-ads' ),
		},
		...adsCampaigns,
	];
	const autocompleter = {
		name: 'programs',
		// Promise.resolve will not be needed after
		// https://github.com/woocommerce/woocommerce-admin/issues/6061
		options: () => {
			return Promise.resolve( programsList );
		},
		getOptionIdentifier: ( option ) => option.id,
		getOptionLabel: ( option ) => option.name,
		getOptionKeywords: ( option ) => [ option.name ],
		// This is slightly different than gutenberg/Autocomplete, we don't support different methods
		// of replace/insertion, so we can just return the value.
		getOptionCompletion: ( program ) => ( {
			key: program.id,
			label: program.name,
		} ),
	};

	function getLabels( param ) {
		// Get program id(s) from query parameter.
		const ids = new Set( getIdsFromQuery( param ) );
		const result = programsList
			.filter( ( campaign ) => ids.has( campaign.id ) )
			.map( ( campaign ) => ( {
				key: campaign.id,
				label: campaign.name,
			} ) );
		return Promise.resolve( result );
	}

	return {
		label: __( 'Show', 'google-listings-and-ads' ),
		staticParams: [
			'period',
			'chartType',
			'paged',
			'per_page',
			'selectedMetric',
		],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{
				label: __( 'All Google programs', 'google-listings-and-ads' ),
				value: 'all',
			},
			{
				label: __( 'Single program', 'google-listings-and-ads' ),
				value: 'select_program',
				subFilters: [
					{
						component: 'Search',
						value: 'single_program',
						// This needs to match value of the parent filter.
						path: [ 'select_program' ],
						settings: {
							type: 'custom',
							param: REPORT_PROGRAM_PARAM,
							getLabels,
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
							autocompleter,
						},
					},
				],
			},
			{
				label: __( 'Comparison', 'google-listings-and-ads' ),
				chartMode: 'item-comparison',
				value: 'compare-programs',
				settings: {
					type: 'custom',
					param: REPORT_PROGRAM_PARAM,
					getLabels,
					labels: {
						helpText: __(
							'Check at least two programs below to compare',
							'google-listings-and-ads'
						),
						placeholder: __(
							'Search for programs to compare',
							'google-listings-and-ads'
						),
						title: __(
							'Compare Programs',
							'google-listings-and-ads'
						),
						update: __( 'Compare', 'google-listings-and-ads' ),
					},
					autocompleter,
				},
			},
		],
	};
};

export const programsFilter = programsFilterConfig;

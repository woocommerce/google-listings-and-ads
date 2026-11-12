/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getIdsFromQuery } from '../utils';
import { FREE_LISTINGS_PROGRAM_ID, REPORT_PROGRAM_PARAM } from '~/constants';

const freeListingsPrograms = [
	{
		id: FREE_LISTINGS_PROGRAM_ID,
		name: __( 'Product Feed', 'google-listings-and-ads' ),
	},
];
const freeListingsProgramsIds = new Set(
	freeListingsPrograms.map( ( program ) => program.id )
);

/**
 * Compares two sets.
 *
 * @param {Set} subset
 * @param {Set} superset
 * @return {boolean} true if these are sets of same elements.
 */
function isSubset( subset, superset ) {
	if ( subset.size > superset.size ) {
		return false;
	}
	for ( const a of subset ) {
		if ( ! superset.has( a ) ) {
			return false;
		}
	}
	return true;
}

export const createProgramsFilterConfig = () => {
	let adsCampaigns;
	let staticPrograms = freeListingsPrograms;
	let resolveAdsCampaigns;
	let promiseProgramsList;

	function waitForNextData() {
		adsCampaigns = null;
		promiseProgramsList = new Promise( ( resolve ) => {
			resolveAdsCampaigns = resolve;
		} ).then( () => {
			return adsCampaigns;
		} );
	}

	// The resolved promise is cached, so `staticPrograms` is added on every read instead.
	// Otherwise a `hasMC` change with the same Ads data would keep the old list.
	function getProgramsList() {
		return promiseProgramsList.then( ( ads ) => {
			return staticPrograms.concat( ads );
		} );
	}

	// Call for initializing
	waitForNextData();

	const autocompleter = {
		name: 'programs',
		options: getProgramsList,
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

	async function getLabels( param ) {
		if ( param === undefined ) {
			return [];
		}

		// Get program id(s) from query parameter.
		const ids = new Set( getIdsFromQuery( param ) );
		let programs;
		// If it's a subset of static values, resolve it immediately.
		if ( isSubset( ids, freeListingsProgramsIds ) ) {
			programs = staticPrograms;
		} else {
			// If there are any paid programs, resolve it once we get it.
			programs = ( await getProgramsList() ).filter( ( campaign ) =>
				ids.has( campaign.id )
			);
		}
		// Map to labels and return.
		return programs.map( ( campaign ) => ( {
			key: campaign.id,
			label: campaign.name,
		} ) );
	}

	const filterConfig = {
		label: __( 'Show', 'google-listings-and-ads' ),
		staticParams: [
			'period',
			'chartType',
			'paged',
			'per_page',
			'selectedMetric',
			'reportKey',
			'orderby',
			'order',
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

	/**
	 * @param {Object} adsCampaigns Result of `useAdsCampaigns`.
	 * @param {Object} adsCampaigns.data Ads campaigns.
	 * @param {boolean} adsCampaigns.loaded Whether the Ads campaigns are loaded.
	 * @param {boolean} [hasMC=true] Whether Merchant Center is set up or connected. Without it, there is no Product Feed program.
	 * @return {Object} The programs filter config.
	 */
	return ( { data, loaded }, hasMC = true ) => {
		staticPrograms = hasMC ? freeListingsPrograms : [];

		if ( loaded ) {
			// Handle the case of no change in `loaded` status between continuous updates.
			if ( adsCampaigns && adsCampaigns !== data ) {
				waitForNextData();
			}

			adsCampaigns = data;
			resolveAdsCampaigns();
		} else if ( adsCampaigns ) {
			waitForNextData();
		}
		return filterConfig;
	};
};

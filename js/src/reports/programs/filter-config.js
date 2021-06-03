/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getIdsFromQuery } from '../utils';
import { FREE_LISTINGS_PROGRAM_ID, REPORT_PROGRAM_PARAM } from '.~/constants';

const freeListingsPrograms = [
	{
		id: FREE_LISTINGS_PROGRAM_ID,
		name: __( 'Free Listings', 'google-listings-and-ads' ),
	},
];
const freeListingsProgramsIds = new Set(
	freeListingsPrograms.map( ( program ) => program.id )
);

/**
 * Compares two sets.
 *
 * @param {Set} setA
 * @param {Set} setB
 * @return {boolean} true if these are sets of same elements.
 */
function equalSet( setA, setB ) {
	if ( setA.size !== setB.size ) return false;
	for ( const a of setA ) {
		if ( ! setB.has( a ) ) {
			return false;
		}
	}
	return true;
}

export const createProgramsFilterConfig = () => {
	let adsCampaigns;
	let resolveAdsCampaigns;
	let promiseProgramsList;

	function waitForNextData() {
		adsCampaigns = null;
		promiseProgramsList = new Promise( ( resolve ) => {
			resolveAdsCampaigns = resolve;
		} ).then( () => {
			return freeListingsPrograms.concat( adsCampaigns );
		} );
	}

	// Call for initializing
	waitForNextData();

	const autocompleter = {
		name: 'programs',
		options: () => promiseProgramsList,
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
		// Get program id(s) from query parameter.
		const ids = new Set( getIdsFromQuery( param ) );
		let programs;
		// If it's one of static values, resolve it immediately.
		if ( equalSet( ids, freeListingsProgramsIds ) ) {
			programs = freeListingsPrograms;
		} else {
			// If there are any paid programs, resolve it once we get it.
			programs = ( await promiseProgramsList ).filter( ( campaign ) =>
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

	return ( { data, loaded } ) => {
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

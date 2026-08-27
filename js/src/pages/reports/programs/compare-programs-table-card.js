/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CompareTableCard from '../compare-table-card';
import { FREE_LISTINGS_PROGRAM_ID } from '~/constants';
import CampaignNameCell from './campaign-name-cell';

const compareBy = 'programs';
const compareParam = 'filter';

/**
 * All programs table, with compare feature.
 *
 * @see AllProgramsTableCard
 * @see AppTableCard
 *
 * @param {Object} props React props.
 * @param {boolean} props.isLoading Whether the data is still being loaded.
 * @param {string} [props.orderby] Key by which the programs should be ordered.
 * @param {string} [props.order] Sorting order, 'desc' or 'asc'.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {Array<FreeListingsData>} props.freeListings Report's programs data.
 * @param {Array<ProgramsData>} props.campaigns Report's programs data.
 * @param {Object} [props.restProps] Properties to be forwarded to CompareTableCard.
 */
const CompareProgramsTableCard = ( {
	isLoading,
	orderby,
	order,
	metrics,
	freeListings,
	campaigns,
	...restProps
} ) => {
	/**
	 * Glue freeListings and campaigns together, hardcode Product Feed name and id.
	 *
	 * @type {Array<ProgramsData>}
	 */
	const programs = useMemo( () => {
		if ( isLoading ) {
			return [];
		}
		if ( ! freeListings || freeListings.length === 0 ) {
			return campaigns;
		}
		// For V1 we assume, there is only one "Product Feed" object.
		const mergedPrograms = [
			{
				...freeListings[ 0 ],
				name: __( 'Product Feed', 'google-listings-and-ads' ),
				id: FREE_LISTINGS_PROGRAM_ID,
			},
			...campaigns,
		];

		// Sort only merged lists. Individual lists should be already sorted by the server-side.
		if ( campaigns.length ) {
			mergedPrograms.sort( ( program1, program2 ) => {
				return (
					// Consider `undefined` the lowest.
					( program1.subtotals[ orderby ] ||
						Number.NEGATIVE_INFINITY ) -
					( program2.subtotals[ orderby ] ||
						Number.NEGATIVE_INFINITY )
				);
			} );
			if ( order === 'desc' ) {
				mergedPrograms.reverse();
			}
		}

		return mergedPrograms;
	}, [ isLoading, freeListings, campaigns, orderby, order ] );

	return (
		<CompareTableCard
			compareButtonTitle={ __(
				'Select one or more programs to compare',
				'google-listings-and-ads'
			) }
			compareBy={ compareBy }
			compareParam={ compareParam }
			data={ programs }
			isLoading={ isLoading }
			metrics={ metrics }
			nameCell={ CampaignNameCell }
			nameHeader={ __( 'Program', 'google-listings-and-ads' ) }
			title={ __( 'Programs', 'google-listings-and-ads' ) }
			{ ...restProps }
		/>
	);
};

export default CompareProgramsTableCard;

/**
 * @typedef {import("../index.js").Metric} Metric
 * @typedef {import("../index.js").FreeListingsData} FreeListingsData
 * @typedef {import("../index.js").ProgramsData} ProgramsData
 */

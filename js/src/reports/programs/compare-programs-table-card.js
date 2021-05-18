/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CompareTableCard from '../compare-table-card';
import { FREE_LISTINGS_PROGRAM_ID } from '.~/constants';

const compareBy = 'programs';
const compareParam = 'filter';

/**
 * All products table, with compare feature.
 *
 * @see AllProgramsTableCard
 * @see AppTableCard
 *
 * @param {Object} props React props.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {boolean} props.isLoading Whether the data is still being loaded.
 * @param {Array<FreeListingsData>} props.freeListings Report's programs data.
 * @param {Array<ProgramsData>} props.campaigns Report's programs data.
 * @param {Object} [props.restProps] Properties to be forwarded to CompareTableCard.
 */
const CompareProgramsTableCard = ( {
	metrics,
	isLoading,
	freeListings,
	campaigns,
	...restProps
} ) => {
	/**
	 * Glue freeListings and campaigns together, hardcode Free Listings name and id.
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
		// For V1 we assume, there is only one "Free listings" object.
		return [
			{
				...freeListings[ 0 ],
				name: __( 'Free Listings', 'google-listings-and-ads' ),
				id: FREE_LISTINGS_PROGRAM_ID,
			},
			...campaigns,
		];
	}, [ isLoading, freeListings, campaigns ] );

	return (
		<CompareTableCard
			title={ __( 'Programs', 'google-listings-and-ads' ) }
			compareButonTitle={ __(
				'Select one or more programs to compare',
				'google-listings-and-ads'
			) }
			nameHeader={ __( 'Program', 'google-listings-and-ads' ) }
			nameCell={ ( row ) => row.name }
			compareBy={ compareBy }
			compareParam={ compareParam }
			metrics={ metrics }
			isLoading={ isLoading }
			data={ programs }
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

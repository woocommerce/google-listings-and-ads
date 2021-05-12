/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

const freeListings = [
	{
		id: 0,
		title: 'Free Listings',
		conversions: 89,
		clicks: 5626,
		impressions: 23340,
		sales: 96.73,
		spend: 0,
		compare: false,
	},
];
const freeListingsMissingData = freeListings.map( ( program ) => {
	return {
		...program,
		conversions: null,
		sales: null,
		spend: null,
	};
} );

const paidPrograms = [
	{
		id: 123,
		title: 'Google Smart Shopping: Fall',
		conversions: 540,
		clicks: 4152,
		impressions: 14339,
		sales: 2527.91,
		spend: 300,
		compare: false,
	},
	{
		id: 456,
		title: 'Google Smart Shopping: Core',
		conversions: 357,
		clicks: 1374,
		impressions: 43359,
		sales: 6204.16,
		spend: 200,
		compare: false,
	},
	{
		id: 789,
		title: 'Google Smart Shopping: Black Friday',
		conversions: 426,
		clicks: 3536,
		impressions: 92771,
		sales: 2091.05,
		spend: 100,
		compare: false,
	},
];

/**
 * Returns mocked Listings Data, according to missingFreeListingsData query parameter.
 *
 * not set or "" - mark all metrics as not missing data.
 * "true" or any truthy value - mark some metrics as missing data from free listings.
 * "na" - not applicable, do not provide metrics that would miss data.
 */
export function mockedListingsData() {
	const { missingFreeListingsData = false } = getQuery();

	if ( missingFreeListingsData === 'na' ) {
		return freeListingsMissingData;
	} else if ( missingFreeListingsData ) {
		return paidPrograms.concat( freeListingsMissingData );
	}
	return paidPrograms.concat( freeListings );
}

export const getProgramLabels = function () {
	return paidPrograms.concat( freeListingsMissingData );
};

/**
 * Returns mocked available metric values, according to missingFreeListingsData query parameter.
 *
 * not set or "" - mark all metrics as not missing data.
 * "true" or any truthy value - mark some metrics as missing data from free listings.
 * "na" - not applicable, do not provide metrics that would miss data.
 */
export function availableMetrics() {
	const { missingFreeListingsData = false } = getQuery();

	const stableMetrics = [ 'clicks', 'impressions', 'spend' ];
	const conditionalMetrics = [ 'sales', 'conversions' ];

	if ( missingFreeListingsData === 'na' ) {
		return stableMetrics;
	}
	return conditionalMetrics.concat( stableMetrics );
}

/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

const products = [
	{
		id: 123,
		title: 'Freshfarm T-shirt',
		conversions: 154,
		clicks: 1148,
		impressions: 70443,
		// itemsSold: 1033,
		totalSales: '$106.58',
		// spend: '$300',
		compare: false,
	},
	{
		id: 456,
		title: "Hershey's Chocolate Hat",
		conversions: 3,
		clicks: 4600,
		impressions: 9631,
		// itemsSold: 456,
		totalSales: '$778.35',
		// spend: '$200',
		compare: false,
	},
	{
		id: 789,
		title: '"Mediterranean Agate A" Fine Art Canvas Print, 54"x54"',
		conversions: 877,
		clicks: 1462,
		impressions: 4339,
		// itemsSold: 877,
		totalSales: '$446.61',
		// spend: '$100',
		compare: false,
	},
	{
		id: 890,
		title: 'Fresh Lemon Fruit Bar',
		conversions: 4,
		clicks: 540,
		impressions: 50364,
		// itemsSold: 877,
		totalSales: '$487.49',
		// spend: '$100',
		compare: false,
	},
	{
		id: 11234,
		title: 'Deny Designs Marta Barragan Camarasa, Green',
		conversions: 2,
		clicks: 798,
		impressions: 93046,
		// itemsSold: 877,
		totalSales: '$419.10',
		// spend: '$100',
		compare: false,
	},
	{
		id: 2234,
		title: 'Extra Tangy Strawberry Tart',
		conversions: 34,
		clicks: 154,
		impressions: 61391,
		// itemsSold: 877,
		totalSales: '$2,359.37',
		// spend: '$100',
		compare: false,
	},
	{
		id: 3345,
		title: 'Pierre Gold Accent Chair',
		conversions: 5,
		clicks: 492,
		impressions: 39235,
		// itemsSold: 877,
		totalSales: '$948.55',
		// spend: '$100',
		compare: false,
	},
	{
		id: 4456,
		title: 'Cookie Hamper',
		conversions: 429,
		clicks: 5045,
		impressions: 50963,
		// itemsSold: 877,
		totalSales: '$778.35',
		// spend: '$100',
		compare: false,
	},
];

/**
 * Returns mocked Products Data.
 */
export function mockedListingsData() {
	return products;
}

export const getProgramLabels = function () {
	return products;
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

	const stableMetrics = [ 'clicks', 'impressions' ];
	const conditionalMetrics = [ 'totalSales', 'conversions' ];

	if ( missingFreeListingsData === 'na' ) {
		return stableMetrics;
	}
	return conditionalMetrics.concat( stableMetrics );
}

/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

const products = [
	{
		id: 123,
		title: 'Freshfarm T-shirt',
		subtotals: {
			conversions: 154,
			clicks: 1148,
			impressions: 70443,
			sales: 106.585,
		},
	},
	{
		id: 456,
		title: "Hershey's Chocolate Hat",
		subtotals: {
			conversions: 3,
			clicks: 4600,
			impressions: 9631,
			sales: 778.354,
		},
	},
	{
		id: 789,
		title: '"Mediterranean Agate A" Fine Art Canvas Print, 54"x54"',
		subtotals: {
			conversions: 877,
			clicks: 1462,
			impressions: 4339,
			sales: 446,
		},
	},
	{
		id: 890,
		title: 'Fresh Lemon Fruit Bar',
		subtotals: {
			conversions: 4,
			clicks: 540,
			impressions: 50364,
			sales: 487.49,
		},
	},
	{
		id: 11234,
		title: 'Deny Designs Marta Barragan Camarasa, Green',
		subtotals: {
			conversions: 2,
			clicks: 798,
			impressions: 93046,
			sales: 419.1,
		},
	},
	{
		id: 2234,
		title: 'Extra Tangy Strawberry Tart',
		subtotals: {
			conversions: 34,
			clicks: 154,
			impressions: 61391,
			sales: 2359.37,
		},
	},
	{
		id: 3345,
		title: 'Pierre Gold Accent Chair',
		subtotals: {
			conversions: 5,
			clicks: 492,
			impressions: 39235,
			sales: 948.55,
		},
	},
	{
		id: 4456,
		title: 'Cookie Hamper',
		subtotals: {
			conversions: 429,
			clicks: 5045,
			impressions: 50963,
			sales: 778.35,
		},
	},
];

/**
 * Returns mocked Products Data.
 */
export function mockedListingsData() {
	return products;
}

/**
 * Returns mocked available metric values, according to missingFreeListingsData query parameter.
 * When it's set to "na" - do not provide metrics that would miss data,
 * otherwise provide all metrics.
 *
 * @return {Array<string>} Array of metric keys.
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

/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Returns mocked Total performance metric values, according to missingFreeListingsData query parameter.
 *
 * not set or "" - mark all metrics as not missing data.
 * "true" or any truthy value - mark some metrics as missing data from free listings.
 * "na" - not applicable, do not provide metrics that would miss data.
 *
 * @return {Array<import('..').TotalsData>} Mocked performance metrics.
 */
export default function () {
	const { missingFreeListingsData = false } = getQuery();

	const data = {
		clicks: {
			value: 14135,
			delta: 0,
			prevValue: 14135,
			label: 'Clicks',
		},
		impressions: {
			value: 383512,
			delta: 1.28,
			prevValue: 378665,
			label: 'Impressions',
		},
	};
	const conditionalData = {
		itemsSold: {
			value: 6928,
			delta: 0.35,
			prevValue: 6903.84,
			label: 'itemsSold',
			missingFreeListingsData,
		},
		conversions: {
			value: 4102,
			delta: -2.21,
			prevValue: 4195,
			label: 'Conversions',
			missingFreeListingsData,
		},
		sales: {
			value: 10802.93,
			delta: 5.35,
			prevValue: 10254.32,
			label: 'Net Sales',
			missingFreeListingsData,
		},
		spend: {
			value: 600,
			delta: -1.97,
			prevValue: 612.05,
			label: 'Total Spend',
			missingFreeListingsData,
		},
	};

	return missingFreeListingsData === 'na'
		? data
		: { ...data, ...conditionalData };
}

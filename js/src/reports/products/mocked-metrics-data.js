/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Returns mocked performance metric values, according to missingFreeListingsData query parameter.
 *
 * not set or "" - mark all metrics as not missing data.
 * "true" or any truthy value - mark some metrics as missing data from free listings.
 * "na" - not applicable, do not provide metrics that would miss data.
 */
export default function () {
	const { missingFreeListingsData = false } = getQuery();

	const data = {
		clicks: {
			value: 14135,
			delta: 0,
			label: 'Clicks',
		},
		impressions: {
			value: 383512,
			delta: 1.28,
			label: 'Impressions',
		},
	};
	const conditionalData = {
		conversions: {
			value: 4102,
			delta: -2.21,
			label: 'Conversions',
			missingFreeListingsData,
		},
		sales: {
			value: 10802.93,
			delta: 5.35,
			label: 'Net Sales',
			missingFreeListingsData,
		},
	};

	return missingFreeListingsData === 'na'
		? data
		: { ...data, ...conditionalData };
}

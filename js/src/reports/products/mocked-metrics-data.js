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
			prevValue: 14135,
			delta: 0,
		},
		impressions: {
			value: 383512,
			prevValue: 378665,
			delta: 1.28,
		},
	};
	const conditionalData = {
		conversions: {
			value: 4102,
			prevValue: 4195,
			delta: -2.21,
			missingFreeListingsData,
		},
		sales: {
			value: 10802.93,
			prevValue: 10254.323,
			delta: 5.35,
			missingFreeListingsData,
		},
	};

	return missingFreeListingsData === 'na'
		? data
		: { ...data, ...conditionalData };
}

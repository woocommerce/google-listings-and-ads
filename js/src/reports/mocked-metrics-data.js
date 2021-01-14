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
 */
export default function () {
	const { missingFreeListingsData = false } = getQuery();

	const data = {
		clicks: {
			value: '14,135',
			delta: 0,
			label: 'Clicks',
		},
		impressions: {
			value: '383,512',
			delta: 1.28,
			label: 'Impressions',
		},
		totalSpend: {
			value: '$600.00',
			delta: -1.97,
			label: 'Total Spend',
		},
	};
	const conditionalData = {
		itemsSold: {
			value: '6,928',
			delta: 0.35,
			label: 'itemsSold',
			missingFreeListingsData,
		},
		conversions: {
			value: '4,102',
			delta: -2.21,
			label: 'Conversions',
			missingFreeListingsData,
		},
		netSales: {
			value: '$10,802.93',
			delta: 5.35,
			label: 'Net Sales',
			missingFreeListingsData,
		},
	};

	return missingFreeListingsData === 'na'
		? data
		: { ...data, ...conditionalData };
}

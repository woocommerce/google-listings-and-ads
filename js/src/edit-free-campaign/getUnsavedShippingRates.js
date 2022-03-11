/**
 * External dependencies
 */
import { isEqual, differenceWith, at } from 'lodash';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRate
 */

/**
 * This function compares the key fields of two shipping rate arrays
 * and returns the unsaved shipping rates.
 *
 * - Order:     The order of two arrays does not need to be the same.
 * - ID:        The `id` is not compared.
 *              On the UI, if a rate was deleted and then added,
 *              then the `id` would not exist.
 * - Threshold: If `options` or `options.free_shipping_threshold` does not exist,
 *              `free_shipping_threshold` is treated as undefined.
 *
 * @param  {Array<ShippingRate>} newShippingRates The new shipping rates to be compared. This can be coming from Form values.
 * @param  {Array<ShippingRate>} oldShippingRates The old shipping rates to be compared against. This should be previously received from API.
 * @return {Array<ShippingRate>} Unsaved shipping rates from newShippingRates.
 */
export default function getUnsavedShippingRates(
	newShippingRates,
	oldShippingRates
) {
	if ( newShippingRates.length !== oldShippingRates.length ) {
		return true;
	}

	const paths = [
		'country',
		'method',
		'currency',
		'rate',
		'options.free_shipping_threshold',
	];

	const diffRates = differenceWith(
		newShippingRates,
		oldShippingRates,
		( a, b ) => {
			return isEqual( at( a, paths ), at( b, paths ) );
		}
	);

	return diffRates;
}

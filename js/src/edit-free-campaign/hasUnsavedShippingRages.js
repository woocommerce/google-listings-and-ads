/**
 * External dependencies
 */
import { isEqual, differenceWith, at } from 'lodash';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRate
 */

/**
 * This function compares the key fields of two shipping rate arrays
 * and returns whether there are any unsaved shipping changes.
 *
 * - Order:     The order of two arrays does not need to be the same.
 * - ID:        The `id` is not compared.
 *              On the UI, if a rate was deleted and then added,
 *              then the `id` would not exist.
 * - Threshold: If `options` or `options.free_shipping_threshold` does not exist,
 *              `free_shipping_threshold` is treated as undefined.
 *
 * @param  {Array<ShippingRate>} rates The shipping rates to be compared.
 * @param  {Array<ShippingRate>} savedRates The saved shipping rates received from API.
 * @return {boolean} Whether there are any unsaved shipping changes.
 */
export default function hasUnsavedShippingRages( rates, savedRates ) {
	if ( rates.length !== savedRates.length ) {
		return true;
	}

	const paths = [
		'country',
		'method',
		'currency',
		'rate',
		'options.free_shipping_threshold',
	];

	const diffRates = differenceWith( rates, savedRates, ( a, b ) => {
		return isEqual( at( a, paths ), at( b, paths ) );
	} );

	return diffRates.length > 0;
}

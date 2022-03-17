/**
 * Internal dependencies
 */
import getDifferentShippingRates from '.~/utils/getDifferentShippingRates';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRate
 */

/**
 * This function compares the key fields of two shipping rate arrays
 * and returns whether there are any unsaved shipping changes.
 *
 * There are unsaved shipping changes when there are:
 *
 * - deleted shipping rates.
 * - newly added shipping rates.
 * - edited shiping rates.
 *
 * Notes:
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
export default function hasUnsavedShippingRates( rates, savedRates ) {
	if ( rates.length !== savedRates.length ) {
		return true;
	}

	const diffRates = getDifferentShippingRates( rates, savedRates );

	return diffRates.length > 0;
}

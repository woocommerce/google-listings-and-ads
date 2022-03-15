/**
 * External dependencies
 */
import { isEqual, differenceWith, at } from 'lodash';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRate
 */

/**
 * Checks if two shipping rates are equal.
 *
 * Note: Shipping rate's `id` is not used here because a shipping rate would have a missing `id`
 * when it is a newly added shipping rate in a form before saving to the database.
 *
 * @param {ShippingRate} a
 * @param {ShippingRate} b
 * @return {boolean} True if the two shipping rates are equal; false otherwise.
 */
const isEqualShippingRate = ( a, b ) => {
	const paths = [
		'country',
		'method',
		'currency',
		'rate',
		'options.free_shipping_threshold',
	];

	return isEqual( at( a, paths ), at( b, paths ) );
};

/**
 * Get shipping rates from `shippingRates1` that are different from shipping rates in `shippingRates2`.
 *
 * @param {Array<ShippingRate>} shippingRates1 Array of shipping rates. This will be used to compare against shippingRates2.
 * @param {Array<ShippingRate>} shippingRates2 Array of shipping rates.
 * @return {Array<ShippingRate>} Array containing shipping rates from shippingRates1 that are different from shipping rates in shippingRates2.
 */
const getDifferentShippingRates = ( shippingRates1, shippingRates2 ) => {
	return differenceWith(
		shippingRates1,
		shippingRates2,
		isEqualShippingRate
	);
};

export default getDifferentShippingRates;

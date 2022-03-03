/**
 * Internal dependencies
 */
import isSameShippingRate from '.~/utils/isSameShippingRate';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRate
 */

/**
 * Check if a shipping rate exists in an array of shipping rates,
 * using `isSameShippingRate` comparison.
 *
 * @param {ShippingRate} shippingRate ShippingRate object.
 * @param {Array<ShippingRate>} shippingRates ShippingRate array.
 * @return {boolean} Boolean result.
 */
const isInShippingRates = ( shippingRate, shippingRates = [] ) => {
	return shippingRates.some( ( el ) =>
		isSameShippingRate( shippingRate, el )
	);
};

export default isInShippingRates;

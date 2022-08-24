/**
 * Internal dependencies
 */
import isNonFreeShippingRate from '.~/utils/isNonFreeShippingRate';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRate
 */

/**
 * Get initial value for offer_free_shipping field, to be used in Form.
 *
 * If all the shipping rates are free shipping,
 * the offer_free_shipping value should be undefined,
 * so that when users add a non-free shipping rate,
 * they would need to choose "Yes" / "No" for offer_free_shipping.
 *
 * If there are non-free shipping rates,
 * the offer_free_shipping value should be true / false
 * based on shippingRate.options.free_shipping_threshold.
 *
 * @param {Array<ShippingRate>} shippingRates Shipping rates.
 * @return {undefined | boolean} Result.
 */
const getOfferFreeShippingInitialValue = ( shippingRates ) => {
	if ( ! shippingRates.some( isNonFreeShippingRate ) ) {
		return undefined;
	}

	return shippingRates.some(
		( el ) => el.options.free_shipping_threshold > 0
	);
};

export default getOfferFreeShippingInitialValue;

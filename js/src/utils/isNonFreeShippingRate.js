/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 */

/**
 * Check if a shipping rate is free or non-free.
 *
 * A shipping rate is free if its rate is 0, and non-free if its rate is greater than 0.
 *
 * @param {ShippingRate} shippingRate Shipping rate.
 * @return {boolean} True if the shippingRate is non-free (rate is greater than 0).
 */
const isNonFreeShippingRate = ( shippingRate ) => shippingRate.rate > 0;

export default isNonFreeShippingRate;

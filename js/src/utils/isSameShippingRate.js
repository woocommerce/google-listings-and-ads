/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 */

/**
 * Checks if two shipping rates are the same.
 *
 * Shipping rates are same if:
 *
 * - they have the same defined id; or,
 * - they have the same country.
 *
 * @param {ShippingRate} a Shipping rate A.
 * @param {ShippingRate} b Shipping rate B.
 * @return {boolean} True or false.
 */
const isSameShippingRate = ( a, b ) => {
	return ( a.id !== undefined && a.id === b.id ) || a.country === b.country;
};

export default isSameShippingRate;

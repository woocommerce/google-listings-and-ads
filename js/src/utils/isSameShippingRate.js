/**
 * Checks if two shipping rates are the same.
 *
 * Shipping rates are same if:
 *
 * - they have the same defined id; or,
 * - they have the same country and method.
 *
 * @param {Object} a Shipping rate A.
 * @param {Object} b Shipping rate B.
 * @return {boolean} True or false.
 */
const isSameShippingRate = ( a, b ) => {
	return (
		( a.id !== undefined && a.id === b.id ) ||
		( a.country === b.country && a.method === b.method )
	);
};

export default isSameShippingRate;

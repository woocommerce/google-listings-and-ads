/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Returns new shipping rates value, with removed group.
 *
 * Will set `shipingRate.options.free_shipping_threshold` to `undefined`
 * for the countries which were in the removed group.
 *
 * @param {Array<ShippingRate>} value Shipping rates value.
 * @param {MinimumOrderGroup} oldGroup The minimum order group.
 */
export const deleteGroup = ( value, oldGroup ) => {
	return value.map( ( shippingRate ) => {
		const newShippingRate = {
			...shippingRate,
			options: {
				...shippingRate.options,
			},
		};

		if ( oldGroup.countries.includes( newShippingRate.country ) ) {
			newShippingRate.options.free_shipping_threshold = undefined;
		}

		return newShippingRate;
	} );
};

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("../typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Returns new Shipping rates value, updated by adding given group.
 *
 * Will set `shipingRate.options.free_shipping_threshold` to `newGroup.threshold`
 * if the shipping rate country is part of `newGroup.countries`.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {MinimumOrderGroup} newGroup The new minimum order group.
 */
export const addGroup = ( value, newGroup ) => {
	return value.map( ( shippingRate ) => {
		const newShippingRate = {
			...shippingRate,
			options: {
				...shippingRate.options,
			},
		};

		if ( newGroup.countries.includes( newShippingRate.country ) ) {
			newShippingRate.options.free_shipping_threshold =
				newGroup.threshold;
		}

		return newShippingRate;
	} );
};

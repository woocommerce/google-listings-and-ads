/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Returns new shipping rates value, with updated group.
 *
 * Will set `shipingRate.options.free_shipping_threshold` to `newGroup.threshold`
 * if the shipping rate country is part of `newGroup.countries`,
 * and set `threshold` to `undefined` for the countries which were removed from the group.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {MinimumOrderGroup} oldGroup Old minimum order group.
 * @param {MinimumOrderGroup} newGroup New minimum order group.
 */
export const changeGroup = ( value, oldGroup, newGroup ) => {
	return value.map( ( shippingRate ) => {
		const newShippingRate = {
			...shippingRate,
			options: {
				...shippingRate.options,
			},
		};

		if ( newGroup.countries.includes( newShippingRate.country ) ) {
			/**
			 * Shipping rate's country exists in the new value countries,
			 * so we just assign the new value threshold.
			 */
			newShippingRate.options.free_shipping_threshold =
				newGroup.threshold;
		} else if ( oldGroup.countries.includes( newShippingRate.country ) ) {
			/**
			 * Shipping rate's country does not exist in the new value countries,
			 * but it exists in the old value countries.
			 * This means users removed the country in the edit modal,
			 * so we set the threshold value to undefined.
			 */
			newShippingRate.options.free_shipping_threshold = undefined;
		}

		return newShippingRate;
	} );
};

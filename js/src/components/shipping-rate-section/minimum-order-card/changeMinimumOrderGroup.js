/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Returns new shipping rates value, with updated group.
 *
 * Will set `shipingRate.options.free_shipping_threshold` to `newGroup.threshold`
 * for the shipping rate countries that are listed in `newGroup.countries` (if provided),
 * and set `threshold` to `undefined` for the countries which were in `oldGroup.countries` (if provided),
 * but not in the new.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {MinimumOrderGroup} [oldGroup] Old minimum order group.
 * @param {MinimumOrderGroup} [newGroup] New minimum order group.
 * @return {Array<ShippingRate>} New, updated value.
 */
export const changeMinimumOrderGroup = ( value, oldGroup, newGroup ) => {
	return value.map( ( shippingRate ) => {
		const newShippingRate = {
			...shippingRate,
			options: {
				...shippingRate.options,
			},
		};

		if ( newGroup?.countries.includes( newShippingRate.country ) ) {
			/**
			 * Shipping rate's country exists in the new value countries,
			 * so we just assign the new value threshold.
			 */
			newShippingRate.options.free_shipping_threshold =
				newGroup.threshold;
		} else if ( oldGroup?.countries.includes( newShippingRate.country ) ) {
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

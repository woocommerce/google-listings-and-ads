/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("../typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Get event handler for adding new minimum order group.
 *
 * Usage: Calling `getMinimumOrderAddHandler(value, onChange)(newGroup)`
 * will trigger `onChange` callback with new `value`.
 * Shipping rates in the new `value` will have their `options.free_shipping_threshold` set to `newGroup.threshold`
 * if the shipping rate country is part of `newGroup.countries`.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {(newValue: Array<ShippingRate>) => void} onChange Callback called with new data when minimum order for shipping rates are changed.
 */
const getMinimumOrderAddHandler = ( value, onChange ) => {
	/**
	 * Event handler for adding new minimum order group.
	 *
	 * @param {MinimumOrderGroup} newGroup The new minimum order group.
	 */
	const handleAdd = ( newGroup ) => {
		const newValue = value.map( ( shippingRate ) => {
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

		onChange( newValue );
	};

	return handleAdd;
};

export default getMinimumOrderAddHandler;

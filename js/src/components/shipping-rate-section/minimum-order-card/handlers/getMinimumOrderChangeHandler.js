/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Get event handler for changing shipping rate minimum order group.
 *
 * The event handler will trigger `onChange` callback with new `value`
 * generated based on `oldGroup` and `newGroup`.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {(newValue: Array<ShippingRate>) => void} onChange Callback called with new data when minimum order for shipping rates are changed.
 * @param {MinimumOrderGroup} oldGroup Old minimum order group.
 */
const getMinimumOrderChangeHandler = ( value, onChange, oldGroup ) => {
	/**
	 * Event handler for changing shipping rate minimum order group.
	 *
	 * @param {MinimumOrderGroup} newGroup New minimum order group.
	 */
	const handleChange = ( newGroup ) => {
		const newValue = value.map( ( shippingRate ) => {
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
			} else if (
				oldGroup.countries.includes( newShippingRate.country )
			) {
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

		onChange( newValue );
	};

	return handleChange;
};

export default getMinimumOrderChangeHandler;

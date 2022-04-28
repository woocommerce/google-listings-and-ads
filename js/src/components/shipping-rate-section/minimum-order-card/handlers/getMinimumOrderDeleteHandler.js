/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Get change handler for shipping rate minimum order.
 *
 * Usage: Calling `getMinimumOrderDeleteHandler(value, onChange)(oldGroup)()`
 * will trigger `onChange` callback with new `value`.
 * Shipping rates in the new `value` will not have the `free_shipping_threshold` value
 * if the shipping rate country is in the `oldGroup` countries.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {(newValue: Array<ShippingRate>) => void} onChange Callback called with new data when minimum order for shipping rates are changed.
 */
const getMinimumOrderDeleteHandler = ( value, onChange ) => {
	/**
	 * Get the `onDelete` event handler for minimum order group.
	 *
	 * @param {MinimumOrderGroup} oldGroup The old minimum order group.
	 */
	const getDeleteHandler = ( oldGroup ) => () => {
		const newValue = value.map( ( shippingRate ) => {
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

		onChange( newValue );
	};

	return getDeleteHandler;
};

export default getMinimumOrderDeleteHandler;

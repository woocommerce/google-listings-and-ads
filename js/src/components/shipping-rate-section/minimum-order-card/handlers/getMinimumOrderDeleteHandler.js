/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Get the event handler for deleting a shipping rate minimum order group.
 *
 * The event handler will trigger `onChange` callback with new `value`.
 * Shipping rates in the new `value` will not have the `free_shipping_threshold` value
 * if the shipping rate country is in the `group` countries.
 *
 * @param {Array<ShippingRate>} value Shipping rates value.
 * @param {(newValue: Array<ShippingRate>) => void} onChange Callback called with new shipping rates value when minimum order for shipping rates are changed.
 * @param {MinimumOrderGroup} group The minimum order group.
 */
const getMinimumOrderDeleteHandler = ( value, onChange, group ) => {
	/**
	 * The event handler for deleting a shipping rate minimum order group.
	 */
	const handleDelete = () => {
		const newValue = value.map( ( shippingRate ) => {
			const newShippingRate = {
				...shippingRate,
				options: {
					...shippingRate.options,
				},
			};

			if ( group.countries.includes( newShippingRate.country ) ) {
				newShippingRate.options.free_shipping_threshold = undefined;
			}

			return newShippingRate;
		} );

		onChange( newValue );
	};

	return handleDelete;
};

export default getMinimumOrderDeleteHandler;

/**
 * A set of event handlers that translates operations done on a single `MinimumOrderGroup` to a change made on all `ShippingRate`s.
 */

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Event handler for adding new minimum order group.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {(newValue: Array<ShippingRate>) => void} onChange Callback called with new data when minimum order for shipping rates are changed.
 * @param {MinimumOrderGroup} newGroup The new minimum order group.
 */
export const handleAdd = ( value, onChange, newGroup ) => {
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

/**
 * Handler for changing minimum order group.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {(newValue: Array<ShippingRate>) => void} onChange Callback called with new data when minimum order for shipping rates are changed.
 * @param {MinimumOrderGroup} oldGroup Old minimum order group.
 * @param {MinimumOrderGroup} newGroup New minimum order group.
 */
export const handleChange = ( value, onChange, oldGroup, newGroup ) => {
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

	onChange( newValue );
};

/**
 * Handler for deleting minimum order group.
 *
 * @param {Array<ShippingRate>} value Shipping rates.
 * @param {(newValue: Array<ShippingRate>) => void} onChange Callback called with new data when minimum order for shipping rates are changed.
 * @param {MinimumOrderGroup} oldGroup The old minimum order group.
 */
export const handleDelete = ( value, onChange, oldGroup ) => {
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

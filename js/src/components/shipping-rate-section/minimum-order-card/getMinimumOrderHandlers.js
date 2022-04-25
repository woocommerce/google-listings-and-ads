/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * @typedef {Object} getMinimumOrderHandlersResult
 * @property {(newGroup: MinimumOrderGroup) => void} handleAddSubmit Handler for add new minimum order group.
 * @property {(oldGroup: MinimumOrderGroup) => (newGroup: MinimumOrderGroup) => void} getChangeHandler Get handler for change minimum order group.
 * @property {(newGroup: MinimumOrderGroup) => () => void} getDeleteHandler Get handler for delete minimum order group.
 */

/**
 * Get handlers for shipping rate minimum order.
 *
 * @param {Object} props
 * @param {Array<ShippingRate>} props.value Shipping rates.
 * @param {(newValue: Array<ShippingRate>) => void} props.onChange Callback called with new data when minimum order for shipping rates are changed.
 * @return {getMinimumOrderHandlersResult} Handlers.
 */
const getMinimumOrderHandlers = ( { value, onChange } ) => {
	/**
	 * Event handler for adding new minimum order group.
	 *
	 * @param {MinimumOrderGroup} newGroup The new minimum order group.
	 */
	const handleAddSubmit = ( newGroup ) => {
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
	 * Get the `onChange` event handler for minimum order group.
	 *
	 * @param {MinimumOrderGroup} oldGroup Old minimum order group.
	 */
	const getChangeHandler = ( oldGroup ) => {
		/**
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

	return { handleAddSubmit, getChangeHandler, getDeleteHandler };
};

export default getMinimumOrderHandlers;

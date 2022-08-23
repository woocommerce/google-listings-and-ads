/**
 * Internal dependencies
 */
import isNonFreeShippingRate from '.~/utils/isNonFreeShippingRate';

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs").ShippingRateGroup } ShippingRateGroup
 */

const defaultShippingRate = {
	options: {},
};

/**
 * Get handlers for EstimatedShippingRatesCard.
 *
 * The handlers are to convert shipping rate group into shipping rates that can be propagated up via `onChange`.
 *
 * @param {Object} props
 * @param {Array<ShippingRate>} props.value Array of individual shipping rates to be used as the initial values of the form.
 * @param {(newValue: Array<ShippingRate>) => void} props.onChange Callback called with new data once shipping rates are changed.
 */
const getHandlers = ( { value, onChange } ) => {
	/**
	 * Event handler for adding new shipping rate group.
	 *
	 * Shipping rate group will be converted into shipping rates, and propagate up via `onChange`.
	 *
	 * @param {ShippingRateGroup} newGroup Shipping rate group.
	 */
	const handleAddSubmit = ( { countries, currency, rate } ) => {
		const newShippingRates = countries.map( ( country ) => ( {
			...defaultShippingRate,
			country,
			currency,
			rate,
		} ) );

		onChange( value.concat( newShippingRates ) );
	};

	/**
	 * Get the `onChange` event handler for shipping rate group.
	 *
	 * @param {ShippingRateGroup} oldGroup Old shipping rate group.
	 */
	const getChangeHandler = ( oldGroup ) => {
		/**
		 * @param {ShippingRateGroup} newGroup New shipping rate group from `onChange` event.
		 */
		const handleChange = ( newGroup ) => {
			/*
			 * Create new shipping rates value by filtering out deleted countries first.
			 *
			 * A country is deleted when it exists in `oldGroup` and not exists in `newGroup`.
			 */
			const newValue = value.filter( ( shippingRate ) => {
				const isDeleted =
					oldGroup.countries.includes( shippingRate.country ) &&
					! newGroup.countries.includes( shippingRate.country );
				return ! isDeleted;
			} );

			/*
			 * Upsert shipping rates in `newValue` by looping through `newGroup.countries`.
			 */
			newGroup.countries.forEach( ( country ) => {
				const existingIndex = newValue.findIndex(
					( shippingRate ) => shippingRate.country === country
				);
				const oldShippingRate = newValue[ existingIndex ];
				const newShippingRate = {
					...defaultShippingRate,
					...oldShippingRate,
					country,
					currency: newGroup.currency,
					rate: newGroup.rate,
				};

				/*
				 * If the shipping rate is free,
				 * we remove the free_shipping_threshold.
				 */
				if ( ! isNonFreeShippingRate( newShippingRate ) ) {
					newShippingRate.options.free_shipping_threshold = undefined;
				}

				if ( existingIndex >= 0 ) {
					newValue[ existingIndex ] = newShippingRate;
				} else {
					newValue.push( newShippingRate );
				}
			} );

			onChange( newValue );
		};

		return handleChange;
	};

	/**
	 * Get the `onDelete` event handler for shipping rate group.
	 *
	 * @param {ShippingRateGroup} oldGroup Shipping rate group.
	 */
	const getDeleteHandler = ( oldGroup ) => () => {
		const newValue = value.filter(
			( shippingRate ) =>
				! oldGroup.countries.includes( shippingRate.country )
		);
		onChange( newValue );
	};

	return { handleAddSubmit, getChangeHandler, getDeleteHandler };
};

export default getHandlers;

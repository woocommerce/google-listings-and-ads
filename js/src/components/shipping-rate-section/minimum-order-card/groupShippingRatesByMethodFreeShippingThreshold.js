const groupShippingRatesByMethodFreeShippingThreshold = ( shippingRates ) => {
	const map = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const threshold =
			shippingRate.options?.free_shipping_threshold === undefined
				? undefined
				: Number( shippingRate.options.free_shipping_threshold );
		const thresholdCurrency = `${ shippingRate.method } ${ threshold } ${ shippingRate.currency }`;

		const group = map.get( thresholdCurrency ) || {
			countries: [],
			method: shippingRate.method,
			threshold,
			currency: shippingRate.currency,
		};
		group.countries.push( shippingRate.country );

		map.set( thresholdCurrency, group );
	} );

	return Array.from( map.values() );
};

export default groupShippingRatesByMethodFreeShippingThreshold;

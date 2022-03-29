const groupShippingRatesByMethodFreeShippingThreshold = ( shippingRates ) => {
	const map = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const {
			method,
			options: { free_shipping_threshold: threshold },
			currency,
		} = shippingRate;

		const methodThresholdCurrency = `${ method } ${ threshold } ${ currency }`;

		const group = map.get( methodThresholdCurrency ) || {
			countries: [],
			method,
			threshold,
			currency,
		};
		group.countries.push( shippingRate.country );

		map.set( methodThresholdCurrency, group );
	} );

	return Array.from( map.values() );
};

export default groupShippingRatesByMethodFreeShippingThreshold;

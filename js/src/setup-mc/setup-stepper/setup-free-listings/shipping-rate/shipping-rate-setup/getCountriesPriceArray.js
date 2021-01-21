const getCountriesPriceArray = ( shippingRates ) => {
	const currency = shippingRates[ 0 ]?.currency;
	const map = new Map();

	shippingRates.forEach( ( el ) => {
		const { countryCode, rate } = el;
		const arr = map.get( rate ) || [];
		arr.push( countryCode );
		map.set( rate, arr );
	} );

	const arr = [];
	for ( const [ rate, countryCodes ] of map ) {
		arr.push( {
			countries: countryCodes,
			price: rate,
			currency,
		} );
	}

	return arr;
};

export default getCountriesPriceArray;

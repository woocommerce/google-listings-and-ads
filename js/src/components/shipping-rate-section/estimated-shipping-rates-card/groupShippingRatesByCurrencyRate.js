/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import("./typedefs").ShippingRateGroup } ShippingRateGroup
 */

/**
 * Groups shipping rates by currency and rate into shipping rate groups.
 *
 * @param {Array<ShippingRate>} shippingRates Array of shipping rates.
 * @return {Array<ShippingRateGroup>} Array of shipping rate groups.
 */
const groupShippingRatesByCurrencyRate = ( shippingRates ) => {
	const rateGroupMap = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const { country, currency, rate } = shippingRate;
		const currencyRate = `${ currency } ${ rate } `;
		const group = rateGroupMap.get( currencyRate ) || {
			countries: [],
			currency,
			rate,
		};
		group.countries.push( country );
		rateGroupMap.set( currencyRate, group );
	} );

	return Array.from( rateGroupMap.values() );
};

export default groupShippingRatesByCurrencyRate;

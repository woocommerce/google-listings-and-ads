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
	const map = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const { country, currency, rate } = shippingRate;
		const key = `${ currency } ${ rate } `;
		const group = map.get( key ) || {
			countries: [],
			currency,
			rate,
		};
		group.countries.push( country );
		map.set( key, group );
	} );

	return Array.from( map.values() );
};

export default groupShippingRatesByCurrencyRate;

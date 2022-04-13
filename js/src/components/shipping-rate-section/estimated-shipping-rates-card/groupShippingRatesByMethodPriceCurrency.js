/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs").ShippingRateGroup } ShippingRateGroup
 */

/**
 * Groups shipping rates based on price and currency.
 *
 * Usage example:
 *
 * ```js
 * const shippingRates = [
 *     {
 *         id: "1",
 *         country: 'US',
 *         method: "flat_rate",
 *         currency: 'USD',
 *         rate: 20,
 *         options: {},
 *     },
 *     {
 *         id: "2",
 *         country: 'AU',
 *         method: "flat_rate",
 *         currency: 'USD',
 *         rate: 20,
 *         options: {},
 *     },
 *     {
 *         id: "3",
 *         country: 'CN',
 *         method: "flat_rate",
 *         currency: 'USD',
 *         rate: 25,
 *         options: {},
 *     },
 *     {
 *         id: "4",
 *         country: 'BR',
 *         method: "flat_rate",
 *         currency: 'BRL',
 *         rate: 20,
 *         options: {},
 *     },
 * ]
 *
 * const result = groupShippingRatesByMethodPriceCurrency( shippingRates );
 *
 * // result:
 * // [
 * //     {
 * //         countries: ['US', 'AU'],
 * //         method: "flat_rate",
 * //         currency: 'USD',
 * //         rate: 20,
 * //     },
 * //     {
 * //         countries: ['CN'],
 * //         method: "flat_rate",
 * //         currency: 'USD',
 * //         rate: 25,
 * //     },
 * //     {
 * //         countries: ['BR'],
 * //         method: "flat_rate",
 * //         currency: 'BRL',
 * //         rate: 20,
 * //     },
 * // ]
 * ```
 *
 * @param {Array<ShippingRate>} shippingRates Array of shipping rates.
 * @return {Array<ShippingRateGroup>} Array of shipping rate groups.
 */
const groupShippingRatesByMethodPriceCurrency = ( shippingRates ) => {
	const rateGroupMap = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const { country, method, currency, rate } = shippingRate;
		const methodCurrencyRate = `${ method } ${ currency } ${ rate } `;
		const group = rateGroupMap.get( methodCurrencyRate ) || {
			countries: [],
			method,
			currency,
			rate,
		};
		group.countries.push( country );
		rateGroupMap.set( methodCurrencyRate, group );
	} );

	return Array.from( rateGroupMap.values() );
};

export default groupShippingRatesByMethodPriceCurrency;

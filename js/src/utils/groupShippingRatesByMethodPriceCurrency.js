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
 *         options: [],
 *     },
 *     {
 *         id: "2",
 *         country: 'AU',
 *         method: "flat_rate",
 *         currency: 'USD',
 *         rate: 20,
 *         options: [],
 *     },
 *     {
 *         id: "3",
 *         country: 'CN',
 *         method: "flat_rate",
 *         currency: 'USD',
 *         rate: 25,
 *         options: [],
 *     },
 *     {
 *         id: "4",
 *         country: 'BR',
 *         method: "flat_rate",
 *         currency: 'BRL',
 *         rate: 20,
 *         options: [],
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
 * //         price: 20,
 * //         currency: 'USD',
 * //     },
 * //     {
 * //         countries: ['CN'],
 * //         method: "flat_rate",
 * //         price: 25,
 * //         currency: 'USD',
 * //     },
 * //     {
 * //         countries: ['BR'],
 * //         method: "flat_rate",
 * //         price: 20,
 * //         currency: 'BRL',
 * //     },
 * // ]
 * ```
 *
 * @param {Array<Object>} shippingRates Array of shipping rates in the format of `{ country, rate, currency }`.
 */
const groupShippingRatesByMethodPriceCurrency = ( shippingRates ) => {
	const rateGroupMap = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const { country, method, rate, currency } = shippingRate;
		const methodPriceCurrency = `${ method } ${ rate } ${ currency }`;
		const group = rateGroupMap.get( methodPriceCurrency ) || {
			countries: [],
			method,
			price: rate,
			currency,
		};
		group.countries.push( country );
		rateGroupMap.set( methodPriceCurrency, group );
	} );

	return Array.from( rateGroupMap.values() );
};

export default groupShippingRatesByMethodPriceCurrency;

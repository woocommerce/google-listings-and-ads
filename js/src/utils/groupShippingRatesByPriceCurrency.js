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
 * const result = groupShippingRatesByPriceCurrency( shippingRates );
 *
 * // result:
 * // [
 * //     {
 * //         countries: ['US', 'AU'],
 * //         price: 20,
 * //         currency: 'USD',
 * //     },
 * //     {
 * //         countries: ['CN'],
 * //         price: 25,
 * //         currency: 'USD',
 * //     },
 * //     {
 * //         countries: ['BR'],
 * //         price: 20,
 * //         currency: 'BRL',
 * //     },
 * ]
 * ```
 *
 * @param {Array<Object>} shippingRates Array of shipping rates in the format of `{ country, rate, currency }`.
 */
const groupShippingRatesByPriceCurrency = ( shippingRates ) => {
	const rateGroupMap = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const { country, rate, currency } = shippingRate;
		const price = Number( rate );
		const priceCurrency = `${ price } ${ currency }`;
		const group = rateGroupMap.get( priceCurrency ) || {
			countries: [],
			price,
			currency,
		};
		group.countries.push( country );
		rateGroupMap.set( priceCurrency, group );
	} );

	return Array.from( rateGroupMap.values() );
};

export default groupShippingRatesByPriceCurrency;

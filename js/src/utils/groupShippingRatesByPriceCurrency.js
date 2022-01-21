/**
 * Groups shipping rates based on price and currency.
 *
 * Usage example:
 *
 * ```js
 * const shippingRates = [
 *     {
 *         country: 'US',
 *         currency: 'USD',
 *         rate: 20,
 *     },
 *     {
 *         country: 'AU',
 *         currency: 'USD',
 *         rate: 20,
 *     },
 *     {
 *         country: 'CN',
 *         currency: 'USD',
 *         rate: 25,
 *     },
 *     {
 *         country: 'BR',
 *         currency: 'BRL',
 *         rate: 20,
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
 * //         rates: [
 * //             {
 * //                 country: 'US',
 * //                 currency: 'USD',
 * //                 rate: 20,
 * //             },
 * //             {
 * //                 country: 'AU',
 * //                 currency: 'USD',
 * //                 rate: 20,
 * //             },
 * //         ]
 * //     },
 * //     {
 * //         countries: ['CN'],
 * //         price: 25,
 * //         currency: 'USD',
 * //         rates: [
 * //             {
 * //                 country: 'CN',
 * //                 currency: 'USD',
 * //                 rate: 25,
 * //             },
 * //         ]
 * //     },
 * //     {
 * //         countries: ['BR'],
 * //         price: 20,
 * //         currency: 'BRL',
 * //         rates: [
 * //             {
 * //                 country: 'BR',
 * //                 currency: 'BRL',
 * //                 rate: 20,
 * //             },
 * //         ]
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
			rates: [],
		};
		group.countries.push( country );
		group.rates.push( shippingRate );
		rateGroupMap.set( priceCurrency, group );
	} );

	return Array.from( rateGroupMap.values() );
};

export default groupShippingRatesByPriceCurrency;

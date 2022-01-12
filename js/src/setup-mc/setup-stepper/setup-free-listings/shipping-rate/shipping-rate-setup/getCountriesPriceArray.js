/**
 * Groups shipping rates based on price and currency.
 *
 * Usage example:
 *
 * ```js
 * const shippingRates = [
 *     {
 *         countryCode: 'US',
 *         currency: 'USD',
 *         rate: 20,
 *     },
 *     {
 *         countryCode: 'AU',
 *         currency: 'USD',
 *         rate: 20,
 *     },
 *     {
 *         countryCode: 'CN',
 *         currency: 'USD',
 *         rate: 25,
 *     },
 *     {
 *         countryCode: 'BR',
 *         currency: 'BRL',
 *         rate: 20,
 *     },
 * ]
 *
 * const result = getCountriesPriceArray( shippingRates );
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
 * @param {Array<Object>} shippingRates Array of shipping rates in the format of `{ countryCode, rate, currency }`.
 */
const getCountriesPriceArray = ( shippingRates ) => {
	const rateGroupMap = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const { countryCode, rate, currency } = shippingRate;
		const price = Number( rate );
		const priceCurrency = `${ price } ${ currency }`;
		const group = rateGroupMap.get( priceCurrency ) || {
			countries: [],
			price,
			currency,
		};
		group.countries.push( countryCode );
		rateGroupMap.set( priceCurrency, group );
	} );

	return Array.from( rateGroupMap.values() );
};

export default getCountriesPriceArray;

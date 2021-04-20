/**
 * Groups shipping rates based on price.
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
 * ]
 * ```
 *
 * @param {Array<Object>} shippingRates Array of shipping rates in the format of `{ countryCode, rate, currency }`.
 */
const getCountriesPriceArray = ( shippingRates ) => {
	const currency = shippingRates[ 0 ]?.currency;
	const rateGroupMap = new Map();

	shippingRates.forEach( ( shippingRate ) => {
		const { countryCode, rate } = shippingRate;
		const price = Number( rate );
		const group = rateGroupMap.get( price ) || {
			countries: [],
			price,
			currency,
		};
		group.countries.push( countryCode );
		rateGroupMap.set( price, group );
	} );

	return Array.from( rateGroupMap.values() );
};

export default getCountriesPriceArray;

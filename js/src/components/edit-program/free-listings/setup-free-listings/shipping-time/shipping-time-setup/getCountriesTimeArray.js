/**
 * Groups shipping times based on time.
 *
 * Usage example:
 *
 * ```js
 * const shippingTimes = [
 *     {
 *         countryCode: 'US',
 *         time: 10
 *     },
 *     {
 *         countryCode: 'AU',
 *         time: 10
 *     },
 *     {
 *         countryCode: 'CN',
 *         time: 15
 *     },
 * ]
 *
 * const result = getCountriesTimeArray( shippingTimes );
 *
 * // result:
 * // [
 * //     {
 * //         countries: ['US', 'AU'],
 * //         time: 10
 * //     },
 * //     {
 * //         countries: ['CN'],
 * //         time: 15
 * //     },
 * ]
 * ```
 *
 * @param {Array<Object>} shippingTimes Array of shipping times in the format of `{ countryCode, time }`.
 */
const getCountriesTimeArray = ( shippingTimes ) => {
	const timeGroupMap = new Map();

	shippingTimes.forEach( ( shippingTime ) => {
		const { countryCode, time } = shippingTime;
		const group = timeGroupMap.get( time ) || {
			countries: [],
			time,
		};
		group.countries.push( countryCode );
		timeGroupMap.set( time, group );
	} );

	return Array.from( timeGroupMap.values() );
};

export default getCountriesTimeArray;

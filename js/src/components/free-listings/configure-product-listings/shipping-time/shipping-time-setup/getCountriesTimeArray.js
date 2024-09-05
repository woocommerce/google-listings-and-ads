/**
 * Groups shipping times based on time.
 *
 * Usage example:
 *
 * ```js
 * const shippingTimes = [
 *     {
 *         countryCode: 'US',
 *         time: 10,
 *		   maxTime: 20,
 *     },
 *     {
 *         countryCode: 'AU',
 *         time: 10,
 *		   maxTime: 20,
 *     },
 *     {
 *         countryCode: 'CN',
 *         time: 15,
 *		   maxTime: 22,
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
 *  //		  maxTime: 20,
 * //     },
 * //     {
 * //         countries: ['CN'],
 * //         time: 15
 * //		  maxTime: 22,
 * //     },
 * ]
 * ```
 *
 * @param {Array<ShippingTime>} shippingTimes Array of individual shipping times in the format of `{ countryCode, time }`.
 * @return {Array<AggregatedShippingTime>} Array of shipping times grouped by time.
 */
const getCountriesTimeArray = ( shippingTimes ) => {
	const timeGroupMap = new Map();

	shippingTimes.forEach( ( shippingTime ) => {
		const { countryCode, time, maxTime } = shippingTime;
		const mapKey = `${ time }-${ maxTime }`;
		const group = timeGroupMap.get( mapKey ) || {
			countries: [],
			time,
			maxTime,
		};
		group.countries.push( countryCode );
		timeGroupMap.set( mapKey, group );
	} );

	return Array.from( timeGroupMap.values() );
};

export default getCountriesTimeArray;

/**
 * @typedef { import(".~/data/actions").ShippingTime } ShippingTime
 * @typedef { import(".~/data/actions").AggregatedShippingTime } AggregatedShippingTime
 */

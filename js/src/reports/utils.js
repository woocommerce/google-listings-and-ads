/**
 * Internal dependencies
 */

import { paidFields, fieldsToPerformance } from '.~/data/utils';

/**
 * Get an array of unique IDs from a comma-separated query parameter.
 * We cannot use '@woocommerce/navigation.getIdsFromQuery' as it does not support `0` as an Id
 * https://github.com/woocommerce/woocommerce-admin/issues/6980
 *
 * @param {string} [queryString=''] string value extracted from URL.
 * @return {Array} List of IDs converted to numbers.
 */
export function getIdsFromQuery( queryString = '' ) {
	return [
		...new Set( // Return only unique ids.
			queryString
				.split( ',' )
				.map( ( id ) => parseInt( id, 10 ) )
				.filter( ( id ) => ! isNaN( id ) )
		),
	];
}

/**
 * @typedef {import(".").IntervalsData} IntervalsData
 * @typedef {import('.').PerformanceData} PerformanceData
 * @typedef {import(".").TotalsData} TotalsData
 */

/**
 * Sums given properties of two objects.
 *
 * @param {Array<string>} metrics Array of property keys.
 * @param {Object} [totals1={}]
 * @param {Object} [totals2={}]
 *
 * @return {Object} New object with given properies.
 */
function sumProperies( metrics, totals1 = {}, totals2 = {} ) {
	return metrics.reduce( ( sum, metric ) => {
		sum[ metric ] = ( totals1[ metric ] || 0 ) + ( totals2[ metric ] || 0 );
		return sum;
	}, {} );
}

/**
 * Aggregate and sort intervals.
 * Sums paid+free subtotals if they overlap.
 *
 * @param {Array<IntervalsData>} intervals1
 * @param {Array<IntervalsData>} intervals2
 * @return {Array<IntervalsData>} Aggregated intervals.
 */
export function aggregateIntervals( intervals1, intervals2 ) {
	// Return early if there is nothing to merge.
	if ( ! intervals2 ) {
		return intervals1;
	} else if ( ! intervals1 ) {
		return intervals2;
	}
	// Convert to Map object
	// Consider doing it on serverside
	const intervals1Map = new Map(
		intervals1.map( ( item ) => [ item.interval, item.subtotals ] )
	);
	const intervals2Map = new Map(
		intervals2.map( ( item ) => [ item.interval, item.subtotals ] )
	);
	const allIntervals = [
		...new Set( [ ...intervals1Map.keys(), ...intervals2Map.keys() ] ),
	].sort();
	// We assume subtotals has the consistent schema across all intervals, and the `paidFields` is the superset.
	const result = allIntervals.reduce( ( acc, interval ) => {
		acc.push( {
			interval,
			subtotals: sumProperies(
				paidFields,
				intervals1Map.get( interval ),
				intervals2Map.get( interval )
			),
		} );
		return acc;
	}, [] );
	return result;
}

/**
 * Maps given totals to performance object,
 * sums paid+free if applicable,
 * flag `missingFreeListingsData` if expected.
 *
 * @param {TotalsData} [paidTotals]
 * @param {TotalsData} [freeTotals]
 * @param {boolean} [expectBoth]
 *
 * @return {PerformanceData} New performance object with sum of totals.
 */
export function sumToPerformance( paidTotals, freeTotals = {}, expectBoth ) {
	const metrics = Object.keys( paidTotals || freeTotals );
	paidTotals = paidTotals || {};
	return metrics.reduce(
		( acc, key ) => ( {
			...acc,
			[ key ]: fieldsToPerformance(
				( paidTotals[ key ] || 0 ) + ( freeTotals[ key ] || 0 ),
				undefined,
				expectBoth && freeTotals[ key ] === undefined
			),
		} ),
		{}
	);
}

/**
 * @param {PerformanceData} performance Current values.
 * @param {PerformanceData} base Values from the previous date.
 * @return {PerformanceData} New object with filled deltas and prevValues.
 */
export function addBaseToPerformance( performance, base ) {
	return Object.keys( performance ).reduce(
		( acc, key ) => ( {
			...acc,
			[ key ]: fieldsToPerformance(
				performance[ key ].value,
				base[ key ].value,
				performance[ key ].missingFreeListingsData ||
					base[ key ].missingFreeListingsData
			),
		} ),
		{}
	);
}

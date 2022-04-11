/**
 * Internal dependencies
 */
import {
	paidFields,
	fieldsToPerformance,
	MISSING_FREE_LISTINGS_DATA,
} from '.~/data/utils';

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
 * @typedef {import("./index").IntervalsData} IntervalsData
 * @typedef {import('./index').PerformanceData} PerformanceData
 * @typedef {import("./index").TotalsData} TotalsData
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
 * @param {Array<IntervalsData>} [intervals1]
 * @param {Array<IntervalsData>} [intervals2]
 * @return {Array<IntervalsData>|null} Aggregated intervals. `null` if both intervals are not provided.
 */
export function aggregateIntervals( intervals1, intervals2 ) {
	// Return early if there is nothing to merge.
	if ( ! intervals2 ) {
		return intervals1 || null;
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
	const result = allIntervals.map( ( interval ) => ( {
		interval,
		subtotals: sumProperies(
			paidFields,
			intervals1Map.get( interval ),
			intervals2Map.get( interval )
		),
	} ) );
	return result;
}

/**
 * Maps given totals to performance object,
 * sums paid+free if applicable,
 * flag `missingFreeListingsData` if expected.
 *
 * @param {TotalsData} [paidTotals]
 * @param {TotalsData} [freeTotals]
 * @param {Array<string>} [expectedFreeFields]
 *
 * @return {PerformanceData} New performance object with sum of totals.
 */
export function sumToPerformance(
	paidTotals,
	freeTotals = {},
	expectedFreeFields
) {
	const metrics = paidTotals ? Object.keys( paidTotals ) : expectedFreeFields;
	paidTotals = paidTotals || {};

	return metrics.reduce( ( acc, key ) => {
		let missingFreeListingsData = MISSING_FREE_LISTINGS_DATA.NONE;
		let value = paidTotals[ key ];

		if ( expectedFreeFields ) {
			if ( ! expectedFreeFields.includes( key ) ) {
				// We do not expect free to come.
				missingFreeListingsData = MISSING_FREE_LISTINGS_DATA.FOR_METRIC;
			} else if ( freeTotals[ key ] === undefined ) {
				// We expected free data to come, but there is none.
				missingFreeListingsData =
					MISSING_FREE_LISTINGS_DATA.FOR_REQUEST;
			} else {
				// There is free listings data, sum with paid one.
				// `freeTotals` doesn't have fallback because it will only be number or undefined type,
				// and the undefined has been checked above.
				value = ( paidTotals[ key ] || 0 ) + freeTotals[ key ];
			}
		}

		return {
			...acc,
			[ key ]: fieldsToPerformance(
				value,
				undefined,
				missingFreeListingsData
			),
		};
	}, {} );
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
				base[ key ]?.value,
				performance[ key ].missingFreeListingsData
			),
		} ),
		{}
	);
}

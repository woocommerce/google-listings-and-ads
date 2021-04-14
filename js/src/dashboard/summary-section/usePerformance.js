/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { getQuery } from '@woocommerce/navigation';
import { getCurrentDates } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import isNaN from '.~/utils/isNaN';
import round from '.~/utils/round';

const isValidNumber = ( number ) => {
	return Number.isFinite( number ) && ! isNaN( number );
};

const mapToData = ( primary, secondary ) => {
	return Object.keys( primary ).reduce( ( acc, key ) => {
		const value = primary[ key ];
		const base = secondary[ key ];
		const percent = ( ( value - base ) / base ) * 100;
		const delta = isValidNumber( percent ) ? round( percent ) : null;
		return {
			...acc,
			[ key ]: { value, delta, prevValue: base },
		};
	}, {} );
};

/**
 * Get performance results by program type.
 * Date-related parameters will be parsed from the URL by this hook.
 *
 * @param  {string} type Type of programs, 'free' or 'paid'.
 * @return {PerformanceSchema} The fetched performance results.
 */
export default function usePerformance( type ) {
	const query = getQuery();
	const currentDate = getCurrentDates( query );
	const deps = [
		type,
		currentDate.primary.range,
		currentDate.secondary.range,
	];

	return useSelect( ( select ) => {
		const { getDashboardPerformance, isResolving } = select( STORE_KEY );
		const primaryArgs = [ type, query, 'primary' ];
		const secondaryArgs = [ type, query, 'secondary' ];
		const primary = getDashboardPerformance( ...primaryArgs );
		const secondary = getDashboardPerformance( ...secondaryArgs );

		let data = {};
		let loading =
			isResolving( 'getDashboardPerformance', primaryArgs ) ||
			isResolving( 'getDashboardPerformance', secondaryArgs );

		if ( primary && secondary ) {
			data = mapToData( primary, secondary );
		} else {
			loading = true;
		}

		return {
			loading,
			data,
		};
	}, deps );
}

/**
 * Performance schema of the `usePerformance` hook
 *
 * @typedef {Object} PerformanceSchema
 * @property {boolean} loading Whether the data is loading.
 * @property {PerformanceData} data Fetched performance data.
 */

/**
 * Performance data of each metric.
 *
 * @typedef {Object} PerformanceData
 * @property {PerformanceMetrics} sales Sales performance.
 * @property {PerformanceMetrics} clicks Clicks performance.
 * @property {PerformanceMetrics} spend Spend performance.
 * @property {PerformanceMetrics} impressions Impressions performance.
 */

/**
 * Performance metrics.
 *
 * @typedef {Object} PerformanceMetrics
 * @property {number} value Value of the current period.
 * @property {number} prevValue Value of the previous period.
 * @property {number} delta The delta of the current value compared to the previous value.
 */

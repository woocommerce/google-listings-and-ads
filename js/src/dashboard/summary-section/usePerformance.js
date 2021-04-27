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
import { mapReportFieldsToPerformance } from '.~/data/utils';

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
			data = mapReportFieldsToPerformance( primary, secondary );
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
 * @typedef { import(".~/data/utils").PerformanceData } PerformanceData
 */

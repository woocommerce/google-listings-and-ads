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
		const { getDashboardPerformance } = select( STORE_KEY );
		const primary = getDashboardPerformance( type, query, 'primary' );
		const secondary = getDashboardPerformance( type, query, 'secondary' );

		let data = null;
		const loaded = primary.loaded && secondary.loaded;

		if ( loaded && primary.data && secondary.data ) {
			data = mapReportFieldsToPerformance( primary.data, secondary.data );
		}

		return {
			loaded,
			data,
		};
	}, deps );
}

/**
 * Performance schema of the `usePerformance` hook
 *
 * @typedef {Object} PerformanceSchema
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {PerformanceData} data Fetched performance data.
 */

/**
 * @typedef { import(".~/data/utils").PerformanceData } PerformanceData
 */

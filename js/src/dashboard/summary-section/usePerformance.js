/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import { mapReportFieldsToPerformance } from '.~/data/utils';
import useUrlQuery from '.~/hooks/useUrlQuery';

/**
 * Get performance results by program type.
 * Date-related parameters will be parsed from the URL by this hook.
 *
 * @param  {string} type Type of programs, 'free' or 'paid'.
 * @return {PerformanceSchema} The fetched performance results.
 */
export default function usePerformance( type ) {
	const query = useUrlQuery();

	return useSelect(
		( select ) => {
			const { getDashboardPerformance } = select( STORE_KEY );
			const primary = getDashboardPerformance( type, query, 'primary' );
			const secondary = getDashboardPerformance(
				type,
				query,
				'secondary'
			);

			let data = null;
			const loaded = primary.loaded && secondary.loaded;

			if ( loaded && primary.data && secondary.data ) {
				data = mapReportFieldsToPerformance(
					primary.data,
					secondary.data
				);
			}

			return {
				loaded,
				data,
			};
		},
		[ type, query ]
	);
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

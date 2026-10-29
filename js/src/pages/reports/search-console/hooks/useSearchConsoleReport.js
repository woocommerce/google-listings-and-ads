/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import useSearchConsoleReportQuery from './useSearchConsoleReportQuery';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleQueriesReport} GoogleSearchConsoleQueriesReport
 * @typedef {import('~/data/types.js').GoogleSearchConsoleTopProductPagesReport} GoogleSearchConsoleTopProductPagesReport
 * @typedef {import('~/data/types.js').GoogleSearchConsoleTopStorePagesReport} GoogleSearchConsoleTopStorePagesReport
 */

/**
 * Get a Search Console report of the date range selected in the URL, once the report is available.
 *
 * @param {string} selectorName Name of the store selector that returns the report of a date range.
 * @return {{
 *   hasFinishedResolution: boolean,
 *   property: string|null,
 *   report: GoogleSearchConsoleQueriesReport|GoogleSearchConsoleTopProductPagesReport|GoogleSearchConsoleTopStorePagesReport|null,
 *   days: number
 * }} The report, the connected property and the number of days the date range covers.
 */
const useSearchConsoleReport = ( selectorName ) => {
	const {
		account,
		isReportAvailable,
		hasFinishedAccount,
		primaryQuery: reportQuery,
		days,
	} = useSearchConsoleReportQuery();

	const { hasFinishedResolution, report } = useSelect(
		( select ) => {
			if ( ! isReportAvailable ) {
				return {
					hasFinishedResolution: hasFinishedAccount,
					report: null,
				};
			}

			const selector = select( STORE_KEY );

			return {
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[ reportQuery ]
				),
				report: selector[ selectorName ]( reportQuery ),
			};
		},
		[ isReportAvailable, hasFinishedAccount, selectorName, reportQuery ]
	);

	return {
		hasFinishedResolution,
		property: account?.site_url ?? null,
		report,
		days,
	};
};

export default useSearchConsoleReport;

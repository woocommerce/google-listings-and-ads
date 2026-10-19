/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { format } from '@wordpress/date';
import {
	getAllowedIntervalsForQuery,
	getCurrentDates,
	getIntervalForQuery,
} from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { fieldsToPerformance, MISSING_FREE_LISTINGS_DATA } from '~/data/utils';
import useUrlQuery from '~/hooks/useUrlQuery';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

const selectorName = 'getSearchConsoleOverviewReport';
const DEFAULT_DATE_RANGE = 'period=month&compare=previous_year';
const METRIC_KEYS = [ 'clicks', 'impressions', 'ctr', 'position' ];
const emptyIntervals = [];

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleOverviewReport} GoogleSearchConsoleOverviewReport
 * @typedef {import('~/data/utils').PerformanceData} PerformanceData
 */

/**
 * Get the Search Console organic overview of the date range and interval selected in the URL,
 * along with the same overview of the comparison period.
 *
 * Nothing is requested until Search Console is connected.
 *
 * @return {{
 *   loaded: boolean,
 *   totals: PerformanceData,
 *   intervals: GoogleSearchConsoleOverviewReport['intervals'],
 *   comparisonIntervals: GoogleSearchConsoleOverviewReport['intervals'],
 *   interval: string,
 *   allowedIntervals: Array<string>,
 *   labels: { primary: string, secondary: string }
 * }} The totals of both periods per metric, the intervals of each period, the interval in use
 *   and the ones allowed for the date range, and the labels of both periods.
 */
const useOrganicOverviewReport = () => {
	const urlQuery = useUrlQuery();
	const { isConnected, hasFinishedResolution: hasFinishedAccount } =
		useGoogleSearchConsoleAccount();

	const { primaryQuery, secondaryQuery, interval, allowedIntervals, labels } =
		useMemo( () => {
			const { primary, secondary } = getCurrentDates(
				urlQuery,
				DEFAULT_DATE_RANGE
			);
			const currentInterval = getIntervalForQuery(
				urlQuery,
				DEFAULT_DATE_RANGE
			);
			const toReportQuery = ( { after, before } ) => {
				return {
					after: format( 'Y-m-d', after ),
					before: format( 'Y-m-d', before ),
					interval: currentInterval,
				};
			};

			return {
				primaryQuery: toReportQuery( primary ),
				secondaryQuery: toReportQuery( secondary ),
				interval: currentInterval,
				allowedIntervals: getAllowedIntervalsForQuery(
					urlQuery,
					DEFAULT_DATE_RANGE
				),
				labels: { primary: primary.label, secondary: secondary.label },
			};
		}, [ urlQuery ] );

	const report = useSelect(
		( select ) => {
			if ( ! isConnected ) {
				return {
					loaded: hasFinishedAccount,
					primary: null,
					secondary: null,
				};
			}

			const selector = select( STORE_KEY );

			return {
				loaded:
					selector.hasFinishedResolution( selectorName, [
						primaryQuery,
					] ) &&
					selector.hasFinishedResolution( selectorName, [
						secondaryQuery,
					] ),
				primary: selector[ selectorName ]( primaryQuery ),
				secondary: selector[ selectorName ]( secondaryQuery ),
			};
		},
		[ isConnected, hasFinishedAccount, primaryQuery, secondaryQuery ]
	);

	const totals = useMemo( () => {
		return METRIC_KEYS.reduce( ( performance, key ) => {
			performance[ key ] = fieldsToPerformance(
				report.primary?.totals?.[ key ],
				report.secondary?.totals?.[ key ],
				MISSING_FREE_LISTINGS_DATA.NONE
			);
			return performance;
		}, {} );
	}, [ report.primary, report.secondary ] );

	return {
		loaded: report.loaded,
		totals,
		intervals: report.primary?.intervals ?? emptyIntervals,
		comparisonIntervals: report.secondary?.intervals ?? emptyIntervals,
		interval,
		allowedIntervals,
		labels,
	};
};

export default useOrganicOverviewReport;

/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { format, getSettings } from '@wordpress/date';
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
import groupDailyIntervals from '../utils/groupDailyIntervals';

const selectorName = 'getSearchConsoleOverviewReport';
const DEFAULT_DATE_RANGE = 'period=month&compare=previous_year';
const METRIC_KEYS = [ 'clicks', 'impressions', 'ctr', 'position' ];
const emptyIntervals = [];

// Search Console only reports daily data, so the report is always requested by day and grouped
// into the selected interval here. It has no hourly data.
const REPORT_INTERVAL = 'day';
const UNSUPPORTED_INTERVALS = [ 'hour' ];

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleOverviewReport} GoogleSearchConsoleOverviewReport
 * @typedef {import('~/data/utils').PerformanceData} PerformanceData
 */

/**
 * Get the Search Console organic overview of the date range and interval selected in the URL,
 * along with the same overview of the comparison period. Both periods are requested by day and
 * grouped into the selected interval.
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
			const supportedIntervals = getAllowedIntervalsForQuery(
				urlQuery,
				DEFAULT_DATE_RANGE
			).filter( ( allowed ) => {
				return ! UNSUPPORTED_INTERVALS.includes( allowed );
			} );
			const requestedInterval = getIntervalForQuery(
				urlQuery,
				DEFAULT_DATE_RANGE
			);
			const toReportQuery = ( { after, before } ) => {
				return {
					after: format( 'Y-m-d', after ),
					before: format( 'Y-m-d', before ),
					interval: REPORT_INTERVAL,
				};
			};

			return {
				primaryQuery: toReportQuery( primary ),
				secondaryQuery: toReportQuery( secondary ),
				interval: supportedIntervals.includes( requestedInterval )
					? requestedInterval
					: supportedIntervals[ 0 ],
				allowedIntervals: supportedIntervals,
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
				// NONE hides MetricNumber's free-listings notice, which doesn't apply here.
				MISSING_FREE_LISTINGS_DATA.NONE
			);
			return performance;
		}, {} );
	}, [ report.primary, report.secondary ] );

	const { intervals, comparisonIntervals } = useMemo( () => {
		const { startOfWeek } = getSettings().l10n;
		const group = ( periodReport, { after } ) => {
			return periodReport?.intervals
				? groupDailyIntervals( periodReport.intervals, {
						after,
						interval,
						startOfWeek,
					} )
				: emptyIntervals;
		};

		return {
			intervals: group( report.primary, primaryQuery ),
			comparisonIntervals: group( report.secondary, secondaryQuery ),
		};
	}, [
		report.primary,
		report.secondary,
		primaryQuery,
		secondaryQuery,
		interval,
	] );

	return {
		loaded: report.loaded,
		totals,
		intervals,
		comparisonIntervals,
		interval,
		allowedIntervals,
		labels,
	};
};

export default useOrganicOverviewReport;

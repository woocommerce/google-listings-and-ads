/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { getSettings } from '@wordpress/date';
import {
	getAllowedIntervalsForQuery,
	getIntervalForQuery,
} from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { fieldsToPerformance, MISSING_FREE_LISTINGS_DATA } from '~/data/utils';
import useUrlQuery from '~/hooks/useUrlQuery';
import groupDailyIntervals from '../utils/groupDailyIntervals';
import useSearchConsoleReportQuery, {
	DEFAULT_DATE_RANGE,
} from './useSearchConsoleReportQuery';

const selectorName = 'getSearchConsoleOverviewReport';
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
 * Nothing is requested until the report is available.
 *
 * @return {{
 *   hasFinishedResolution: boolean,
 *   totals: PerformanceData,
 *   intervals: GoogleSearchConsoleOverviewReport['intervals'],
 *   comparisonIntervals: GoogleSearchConsoleOverviewReport['intervals'],
 *   intervalUnit: string,
 *   allowedIntervals: Array<string>,
 *   labels: { primary: string, secondary: string }
 * }} The totals of both periods per metric, the intervals of each period, the interval in use
 *   and the ones allowed for the date range, and the labels of both periods.
 */
const useOrganicOverviewReport = () => {
	const urlQuery = useUrlQuery();
	const {
		isReportAvailable,
		hasFinishedAccount,
		primaryQuery: primaryDates,
		secondaryQuery: secondaryDates,
		labels,
	} = useSearchConsoleReportQuery();

	const { primaryQuery, secondaryQuery } = useMemo( () => {
		return {
			primaryQuery: { ...primaryDates, interval: REPORT_INTERVAL },
			secondaryQuery: { ...secondaryDates, interval: REPORT_INTERVAL },
		};
	}, [ primaryDates, secondaryDates ] );

	const { intervalUnit, allowedIntervals } = useMemo( () => {
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

		return {
			intervalUnit: supportedIntervals.includes( requestedInterval )
				? requestedInterval
				: supportedIntervals[ 0 ],
			allowedIntervals: supportedIntervals,
		};
	}, [ urlQuery ] );

	const report = useSelect(
		( select ) => {
			if ( ! isReportAvailable ) {
				return {
					hasFinishedResolution: hasFinishedAccount,
					primary: null,
					secondary: null,
				};
			}

			const selector = select( STORE_KEY );

			return {
				hasFinishedResolution:
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
		[ isReportAvailable, hasFinishedAccount, primaryQuery, secondaryQuery ]
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
						interval: intervalUnit,
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
		intervalUnit,
	] );

	return {
		hasFinishedResolution: report.hasFinishedResolution,
		totals,
		intervals,
		comparisonIntervals,
		intervalUnit,
		allowedIntervals,
		labels,
	};
};

export default useOrganicOverviewReport;

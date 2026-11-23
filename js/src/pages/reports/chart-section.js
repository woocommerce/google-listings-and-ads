/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { format } from '@wordpress/date';
import { Chart } from '@woocommerce/components';
import { getChartTypeForQuery } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import useUrlQuery from '~/hooks/useUrlQuery';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import { getComparisonChartData } from './utils';

const emptyMessage = __(
	'No data for the selected date range',
	'google-listings-and-ads'
);

/**
 * Utility to generate all dates between start and end (inclusive)
 *
 * @param {string} start - Start date in YYYY-MM-DD format.
 * @param {string} end - End date in YYYY-MM-DD format.
 * @return {string[]} Array of date strings in YYYY-MM-DD format.
 */
function generateDateRange( start, end ) {
	const dates = [];
	const current = new Date( start );
	const endDate = new Date( end );

	while ( current <= endDate ) {
		dates.push( format( 'Y-m-d', current ) );
		current.setDate( current.getDate() + 1 );
	}
	return dates;
}

/**
 * The largest value for `key` within one period's intervals, or 0 if there are none.
 *
 * @param {Array<IntervalsData>} [intervals] Intervals data of one period.
 * @param {string} key Key of the metric to plot.
 * @return {number} The largest value, or 0.
 */
function getPeriodMax( intervals, key ) {
	return Math.max(
		0,
		...( intervals ?? [] ).map( ( { subtotals } ) => {
			return subtotals[ key ] ?? 0;
		} )
	);
}

/**
 * Picks a power-of-10 scale factor so an all-sub-1 metric (e.g. CTR) stays out of the value
 * domain where Chart's Y-axis breaks. Scales from the smaller period's own max, not the
 * combined max, so the smaller one still clears the broken range if it's toggled on alone.
 *
 * @param {Array<IntervalsData>} intervals Intervals data of the selected period.
 * @param {ChartComparison} comparison Data of the comparison period.
 * @param {string} key Key of the metric to plot.
 * @return {number} Scale factor to multiply every plotted value by.
 */
function getChartValueScale( intervals, comparison, key ) {
	const periodMaxes = [
		getPeriodMax( intervals, key ),
		getPeriodMax( comparison.intervals, key ),
	].filter( ( max ) => {
		return max > 0;
	} );
	const smallestMax = periodMaxes.length ? Math.min( ...periodMaxes ) : 0;

	if ( smallestMax === 0 || smallestMax >= 1 ) {
		return 1;
	}

	let scale = 1;
	while ( smallestMax * scale < 10 ) {
		scale *= 10;
	}

	return scale;
}

/**
 * Multiplies a value by `scale`, passing `undefined` through unchanged.
 *
 * @param {number} [value] Value to scale.
 * @param {number} scale Scale factor from {@link getChartValueScale}.
 * @return {number|undefined} The scaled value.
 */
function scaleValue( value, scale ) {
	return value === undefined ? undefined : value * scale;
}

/**
 * Renders a report chart.
 *
 * With `comparison`, it plots the selected and the comparison periods as two lines, each in
 * the legend with its total and a checkbox to show or hide it. Both periods are plotted as given,
 * one point per interval, matched by position, so missing intervals must already be filled in.
 *
 * @param {Object} props React props.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {boolean} props.loaded Whether the data have been loaded.
 * @param {Array<IntervalsData>} props.intervals Report's intervals data.
 * @param {ChartComparison} [props.comparison] Data to plot a comparison period alongside `intervals`: its intervals, plus the labels and totals of both periods.
 * @param {string} [props.intervalUnit] Unit of time the intervals data is broken down by, e.g. `day` or `week`.
 * @param {Array<string>} [props.allowedIntervals] Intervals the merchant can switch between. The interval selector is shown when there is any.
 */
export default function ChartSection( {
	metrics,
	loaded,
	intervals,
	comparison,
	intervalUnit,
	allowedIntervals,
} ) {
	const query = useUrlQuery();
	const storeCurrencyConfig = useStoreCurrency();

	const { selectedMetric } = query;
	let visibleMetric = {};
	if ( metrics.length ) {
		visibleMetric =
			( selectedMetric &&
				metrics.find( ( metric ) => metric.key === selectedMetric ) ) ||
			metrics[ 0 ];
	}

	const { key, label, isCurrency = false, formatFn } = visibleMetric;

	// Preferably we would use the currency of the selected metric to be used on y axis.
	// But due to https://github.com/woocommerce/woocommerce-admin/issues/7694 Chart will not react on changes.
	// Therefore, we will use sotre's one without the symbol, to slightly reduce the merchants confusion.
	const visibleCurrency = { ...storeCurrencyConfig, symbol: '' };
	const chartType = getChartTypeForQuery( query );
	const valueType = isCurrency ? 'currency' : 'number';
	// Only comparison charts are scaled, so a chart without a comparison period plots and formats its values as given.
	const scale = comparison
		? getChartValueScale( intervals, comparison, key )
		: 1;
	// Formats an already-scaled value, for what Chart itself renders.
	const localizedChartFormatFn = ( value ) => {
		return formatFn.call( visibleMetric, value / scale );
	};

	const chartData = useMemo( () => {
		if ( ! loaded || ! intervals.length ) {
			return [];
		}

		if ( comparison ) {
			return getComparisonChartData( intervals, comparison, key, scale );
		}

		// Map existing intervals for quick lookup
		const intervalMap = intervals.reduce(
			( acc, { interval, subtotals } ) => {
				acc[ interval ] = subtotals;
				return acc;
			},
			{}
		);

		// Generate full date range
		const startDate = intervals[ 0 ].interval;
		const endDate = intervals[ intervals.length - 1 ].interval;
		const allDates = generateDateRange( startDate, endDate );

		// Build chart data ensuring all dates are included
		return allDates.map( ( date ) => {
			const subtotals = intervalMap[ date ] || { [ key ]: 0 };
			return {
				date,
				[ label ]: {
					value: subtotals[ key ] ?? 0,
					label,
				},
			};
		} );
	}, [ key, label, loaded, intervals, comparison, scale ] );

	const comparisonProps = comparison && {
		yFormat: localizedChartFormatFn,
		yBelow1Format: localizedChartFormatFn,
		interval: intervalUnit,
		allowedIntervals,
		legendTotals: {
			primary: scaleValue( comparison.totals[ key ]?.value, scale ),
			secondary: scaleValue( comparison.totals[ key ]?.prevValue, scale ),
		},
	};

	return (
		<Chart
			data={ chartData }
			title={ label }
			query={ query }
			currency={ visibleCurrency }
			chartType={ chartType }
			valueType={ valueType }
			tooltipValueFormat={ localizedChartFormatFn }
			isRequesting={ ! loaded }
			emptyMessage={ emptyMessage }
			legendPosition={ comparison ? undefined : 'hidden' }
			{ ...comparisonProps }
		/>
	);
}

/**
 * @typedef {import("./index.js").Metric} Metric
 * @typedef {import("./index.js").IntervalsData} IntervalsData
 * @typedef {import("~/data/utils").PerformanceData} PerformanceData
 */

/**
 * @typedef {Object} ChartComparison
 * @property {Array<IntervalsData>} intervals Intervals data of the comparison period.
 * @property {PerformanceData} totals Totals of both periods, per metric.
 * @property {{ primary: string, secondary: string }} labels Labels of both periods.
 */

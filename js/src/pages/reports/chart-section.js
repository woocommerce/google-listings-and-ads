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
 * Picks a power-of-10 scale factor for a metric whose combined max across both periods is
 * below 1 (1, i.e. unscaled, for every other metric). Chart's own Y-axis scale (`calculateStep`
 * in `@woocommerce/components`) breaks and renders nothing for an all-sub-1 value domain;
 * metrics like CTR are always a 0-1 fraction, so they need this to chart at all.
 *
 * Scaling to the combined max of both periods, with a ×10 margin rather than just ≥1, also
 * covers unchecking a legend series: Chart recomputes its Y-axis from only the still-visible
 * series, so the remaining one needs headroom even though it wasn't the one that set the scale.
 *
 * @param {Array<IntervalsData>} intervals Intervals data of the selected period.
 * @param {ChartComparison} [comparison] Data of the comparison period, if any.
 * @param {string} key Key of the metric to plot.
 * @return {number} Scale factor to multiply every plotted value by.
 */
function getChartValueScale( intervals, comparison, key ) {
	const maxValue = Math.max(
		0,
		...intervals.map( ( { subtotals } ) => subtotals[ key ] ?? 0 ),
		...( comparison?.intervals ?? [] ).map(
			( { subtotals } ) => subtotals[ key ] ?? 0
		)
	);

	if ( maxValue === 0 || maxValue >= 1 ) {
		return 1;
	}

	let scale = 1;
	while ( maxValue * scale < 10 ) {
		scale *= 10;
	}

	return scale;
}

/**
 * Multiplies a value by `scale`, passing `undefined` through unchanged.
 *
 * @param {number} [value] Value to scale.
 * @param {number} scale Scale factor from {@see getChartValueScale}.
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
	const scale = getChartValueScale( intervals, comparison, key );
	// Formats a value already multiplied by `scale`, for everything Chart itself renders
	// (tooltip, Y-axis, legend) -- as opposed to `formatFn`, which formats a raw value and is
	// used by the summary cards above the chart instead.
	const localizedChartFormatFn = ( value ) =>
		formatFn.call( visibleMetric, value / scale );

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
					value: ( subtotals[ key ] ?? 0 ) * scale,
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

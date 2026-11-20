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

	const {
		key,
		label,
		isCurrency = false,
		formatFn,
		chartValueScale = 1,
		chartFormatFn = formatFn,
	} = visibleMetric;

	// Preferably we would use the currency of the selected metric to be used on y axis.
	// But due to https://github.com/woocommerce/woocommerce-admin/issues/7694 Chart will not react on changes.
	// Therefore, we will use sotre's one without the symbol, to slightly reduce the merchants confusion.
	const visibleCurrency = { ...storeCurrencyConfig, symbol: '' };
	const chartType = getChartTypeForQuery( query );
	const valueType = isCurrency ? 'currency' : 'number';
	// Formats a value already multiplied by `chartValueScale`, for everything Chart itself
	// renders (tooltip, Y-axis, legend) -- as opposed to `formatFn`, which formats a raw value
	// and is used by the summary cards above the chart instead.
	const localizedChartFormatFn = chartFormatFn.bind( visibleMetric );

	const chartData = useMemo( () => {
		if ( ! loaded || ! intervals.length ) {
			return [];
		}

		if ( comparison ) {
			return getComparisonChartData(
				intervals,
				comparison,
				key,
				chartValueScale
			);
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
					value: ( subtotals[ key ] ?? 0 ) * chartValueScale,
					label,
				},
			};
		} );
	}, [ key, label, loaded, intervals, comparison, chartValueScale ] );

	// Chart's own totals (legend, tooltip) need the same scaling as the plotted data above, so
	// they stay consistent with it and with `localizedChartFormatFn`.
	const scaleLegendTotal = ( value ) =>
		value === undefined ? undefined : value * chartValueScale;

	const comparisonProps = comparison && {
		yFormat: localizedChartFormatFn,
		yBelow1Format: localizedChartFormatFn,
		legendValueFormat: localizedChartFormatFn,
		interval: intervalUnit,
		allowedIntervals,
		legendTotals: {
			primary: scaleLegendTotal( comparison.totals[ key ]?.value ),
			secondary: scaleLegendTotal( comparison.totals[ key ]?.prevValue ),
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

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
 * @param {ChartComparison} [props.comparison] The comparison period to plot alongside `intervals`.
 * @param {string} [props.interval] Interval the intervals data is broken down by, e.g. `day`.
 * @param {Array<string>} [props.allowedIntervals] Intervals the merchant can switch between. The interval selector is shown when there is any.
 */
export default function ChartSection( {
	metrics,
	loaded,
	intervals,
	comparison,
	interval,
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
	const localizedFormatFn = formatFn.bind( visibleMetric );

	const chartData = useMemo( () => {
		if ( ! loaded || ! intervals.length ) {
			return [];
		}

		if ( comparison ) {
			// `?? 0` is kept even for a `null` CTR/position (no impressions that interval): the
			// underlying d3 line has no `defined()` accessor, so a `null` point isn't skipped as a
			// gap -- its scale coerces `null` to 0 anyway, the same place `0` lands. A `null` value
			// would read identically on the chart; the summary cards above are where `null` actually
			// matters, since `calculateDelta()` turns it into "Unavailable" there instead of a
			// misleading 0/-100%.
			return intervals.map( ( primary, index ) => {
				const secondary = comparison.intervals[ index ];
				const date = `${ primary.interval }T00:00:00`;

				return {
					date,
					primary: {
						label: comparison.labels.primary,
						value: primary.subtotals[ key ] ?? 0,
						labelDate: date,
					},
					secondary: {
						label: comparison.labels.secondary,
						value: secondary?.subtotals[ key ] ?? 0,
						labelDate: secondary
							? `${ secondary.interval }T00:00:00`
							: undefined,
					},
				};
			} );
		}

		// Map existing intervals for quick lookup
		const intervalMap = intervals.reduce(
			( acc, { interval: date, subtotals } ) => {
				acc[ date ] = subtotals;
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
	}, [ key, label, loaded, intervals, comparison ] );

	if ( comparison ) {
		return (
			<Chart
				data={ chartData }
				title={ label }
				query={ query }
				currency={ visibleCurrency }
				chartType={ chartType }
				valueType={ valueType }
				tooltipValueFormat={ localizedFormatFn }
				yFormat={ localizedFormatFn }
				yBelow1Format={ localizedFormatFn }
				isRequesting={ ! loaded }
				emptyMessage={ emptyMessage }
				interval={ interval }
				allowedIntervals={ allowedIntervals }
				legendTotals={ {
					primary: comparison.totals[ key ]?.value,
					secondary: comparison.totals[ key ]?.prevValue,
				} }
			/>
		);
	}

	return (
		<Chart
			data={ chartData }
			title={ label }
			query={ query }
			currency={ visibleCurrency }
			chartType={ chartType }
			valueType={ valueType }
			tooltipValueFormat={ localizedFormatFn }
			isRequesting={ ! loaded }
			emptyMessage={ emptyMessage }
			layout="time-comparison"
			legendPosition="hidden"
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

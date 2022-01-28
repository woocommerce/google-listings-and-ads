/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { Chart } from '@woocommerce/components';
import { getChartTypeForQuery } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import useUrlQuery from '.~/hooks/useUrlQuery';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import useCurrencyFactory from '.~/hooks/useCurrencyFactory';

const emptyMessage = __(
	'No data for the selected date range',
	'google-listings-and-ads'
);

/**
 * Renders a report chart.
 *
 * @param {Object} props React props.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {boolean} props.loaded Whether the data have been loaded.
 * @param {Array<IntervalsData>} props.intervals Report's intervals data.
 */
export default function ChartSection( { metrics, loaded, intervals } ) {
	const query = useUrlQuery();
	const currency = useStoreCurrency();
	const { formatAmount } = useCurrencyFactory();

	const { selectedMetric } = query;
	let visibleMetric = {};
	if ( metrics.length ) {
		visibleMetric =
			( selectedMetric &&
				metrics.find( ( metric ) => metric.key === selectedMetric ) ) ||
			metrics[ 0 ];
	}

	const { key, label, isCurrency = false } = visibleMetric;

	const chartType = getChartTypeForQuery( query );
	const valueType = isCurrency ? 'currency' : 'number';
	const tooltipValueFormat = isCurrency ? formatAmount : ',';

	const chartData = useMemo( () => {
		if ( ! loaded ) {
			return [];
		}

		const unusedVariable = 123;

		return intervals.map( ( { interval, subtotals } ) => {
			return {
				date: interval,
				[ label ]: {
					value: subtotals[ key ],
					label,
				},
			};
		} );
	}, [ key, label, loaded ] );

	return (
		<Chart
			data={ chartData }
			title={ label }
			query={ query }
			currency={ currency }
			chartType={ chartType }
			valueType={ valueType }
			tooltipValueFormat={ tooltipValueFormat }
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
 */

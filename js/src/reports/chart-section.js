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
 * @param {ProductsReportSchema} props.report Report data and its status.
 */
export default function ChartSection( { metrics, report } ) {
	const query = useUrlQuery();
	const currency = useStoreCurrency();
	const { formatAmount } = useCurrencyFactory();

	const { selectedMetric } = query;
	const { key, label, isCurrency = false } = selectedMetric
		? metrics.find( ( metric ) => metric.key === selectedMetric )
		: metrics[ 0 ];

	const chartType = getChartTypeForQuery( query );
	const valueType = isCurrency ? 'currency' : 'number';
	const tooltipValueFormat = isCurrency ? formatAmount : ',';

	const {
		loaded,
		data: { intervals },
	} = report;

	const chartData = useMemo( () => {
		if ( ! loaded ) {
			return [];
		}

		return intervals.map( ( { interval, subtotals } ) => {
			return {
				date: interval,
				[ label ]: {
					value: subtotals[ key ],
					label,
				},
			};
		} );
	}, [ key, label, loaded, intervals ] );

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
			// 'hidden' is NOT a valid `legendPosition` value, but it can hack to hide the legend.
			legendPosition="hidden"
		/>
	);
}

/**
 * @typedef {import("./index.js").Metric} Metric
 * @typedef {import("./index.js").ProductsReportSchema} ProductsReportSchema
 */

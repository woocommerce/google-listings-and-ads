/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { Chart } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useUrlQuery from '.~/hooks/useUrlQuery';

/**
 * Renders a report chart.
 *
 * @param {Object} props React props.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {ProductsReportSchema} props.report Report data and its status.
 */
export default function ChartSection( { metrics, report } ) {
	const query = useUrlQuery();
	const { orderby } = query;
	const { key, label } = orderby
		? metrics.find( ( metric ) => metric.key === orderby )
		: metrics[ 0 ];

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
			isRequesting={ ! loaded }
			layout="time-comparison"
			interactiveLegend="false"
			showHeaderControls="false"
		/>
	);
}

/**
 * @typedef {import("./index.js").Metric} Metric
 * @typedef {import("./index.js").ProductsReportSchema} ProductsReportSchema
 */

/**
 * External dependencies
 */
import { SummaryList } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import MetricNumber from '../metric-number';
import getMetricsData from './mocked-metrics-data';

/**
 * Renders a section composed with SummaryList and MetricNumber.
 *
 * @param {Object} props React props.
 * @param {[[string, string, boolean]]} props.metrics Metrics array of each metric tuple [key, label, isCurrency=false].
 */
export default function SummarySection( { metrics } ) {
	const metricsData = getMetricsData();

	return (
		<SummaryList>
			{ () =>
				metrics.map( ( [ key, label, isCurrency = false ] ) => {
					return (
						<MetricNumber
							key={ key }
							label={ label }
							isCurrency={ isCurrency }
							data={ metricsData[ key ] }
						/>
					);
				} )
			}
		</SummaryList>
	);
}

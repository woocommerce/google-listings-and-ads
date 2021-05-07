/**
 * External dependencies
 */
import { SummaryList } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useUrlQuery from '.~/hooks/useUrlQuery';
import MetricNumber from '../metric-number';
import getMetricsData from './mocked-metrics-data';

/**
 * Renders a section composed with SummaryList and MetricNumber.
 *
 * @param {Object} props React props.
 * @param {[[string, string, boolean]]} props.metrics Metrics array of each metric tuple [key, label, isCurrency=false].
 */
export default function SummarySection( { metrics } ) {
	const query = useUrlQuery();
	const metricsData = getMetricsData();
	const { orderby = metrics[ 0 ][ 0 ] } = query;

	return (
		<SummaryList>
			{ () =>
				metrics.map( ( [ key, label, isCurrency = false ] ) => {
					const selected = orderby === key;
					const href = getNewPath( { orderby: key } );
					return (
						<MetricNumber
							key={ key }
							label={ label }
							href={ href }
							selected={ selected }
							isCurrency={ isCurrency }
							data={ metricsData[ key ] }
						/>
					);
				} )
			}
		</SummaryList>
	);
}

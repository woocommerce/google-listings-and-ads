/**
 * External dependencies
 */
import { SummaryList, SummaryListPlaceholder } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useUrlQuery from '.~/hooks/useUrlQuery';
import MetricNumber from './metric-number';

/**
 * Renders a section composed with SummaryList and MetricNumber.
 *
 * @param {Object} props React props.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {boolean} props.loaded Whether the data have been loaded.
 * @param {PerformanceData} props.totals Report's performance data.
 */
export default function SummarySection( { metrics, loaded, totals } ) {
	const query = useUrlQuery();
	const { selectedMetric = metrics[ 0 ].key } = query;

	if ( ! loaded ) {
		return <SummaryListPlaceholder numberOfItems={ metrics.length } />;
	}

	return (
		<SummaryList>
			{ () =>
				metrics.map( ( { key, label, isCurrency = false } ) => {
					const selected = selectedMetric === key;
					const href = getNewPath( { selectedMetric: key } );
					return (
						<MetricNumber
							key={ key }
							label={ label }
							href={ href }
							selected={ selected }
							isCurrency={ isCurrency }
							data={ totals[ key ] }
						/>
					);
				} )
			}
		</SummaryList>
	);
}

/**
 * @typedef {import("./index.js").Metric} Metric
 * @typedef {import(".~/data/utils").PerformanceData } PerformanceData
 */

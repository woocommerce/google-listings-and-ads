/**
 * External dependencies
 */
import { SummaryList, SummaryListPlaceholder } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { recordChartTabClickEvent } from '.~/utils/recordEvent';
import useUrlQuery from '.~/hooks/useUrlQuery';
import MetricNumber from './metric-number';

const noValidData = {
	value: null,
	preValue: null,
	delta: null,
};

/**
 * Renders a section composed with SummaryList and MetricNumber.
 *
 * @param {Object} props React props.
 * @param {boolean} props.loaded Whether the data have been loaded.
 * @param {Array<Metric>} props.metrics Metrics to display.
 * @param {number} [props.expectedLength=metrics.length] Expected metrics to display, for example when the metrics array is not yet resolved.
 * @param {PerformanceData} props.totals Report's performance data.
 * @param {string} props.trackEventId Report ID used in tracking events.
 */
export default function SummarySection( {
	loaded,
	metrics,
	expectedLength = metrics.length,
	totals,
	trackEventId,
} ) {
	const query = useUrlQuery();
	if ( ! loaded ) {
		return <SummaryListPlaceholder numberOfItems={ expectedLength } />;
	}

	const { selectedMetric = metrics[ 0 ].key } = query;

	const trackClickEvent = ( context ) => {
		recordChartTabClickEvent( {
			report: trackEventId,
			context,
		} );
	};

	return (
		<SummaryList>
			{ () =>
				metrics.map( ( metric ) => {
					const { key } = metric;
					const selected = selectedMetric === key;
					const href = getNewPath( { selectedMetric: key } );
					return (
						<MetricNumber
							key={ key }
							metric={ metric }
							href={ href }
							selected={ selected }
							data={ totals[ key ] || noValidData }
							onLinkClickCallback={ () => trackClickEvent( key ) }
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

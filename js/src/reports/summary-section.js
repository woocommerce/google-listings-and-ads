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
 * @param {ProductsReportSchema} props.report Report data and its status.
 */
export default function SummarySection( { metrics, report } ) {
	const query = useUrlQuery();
	const { orderby = metrics[ 0 ].key } = query;
	const {
		loaded,
		data: { totals },
	} = report;

	if ( ! loaded ) {
		return <SummaryListPlaceholder numberOfItems={ metrics.length } />;
	}

	return (
		<SummaryList>
			{ () =>
				metrics.map( ( { key, label, isCurrency = false } ) => {
					const selected = orderby === key;
					const href = getNewPath( { orderby: key } );
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
 * @typedef {import("./index.js").ProductsReportSchema} ProductsReportSchema
 */

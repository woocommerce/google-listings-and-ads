/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SummarySection from '../../summary-section';
import ChartSection from '../../chart-section';
import useOrganicOverviewReport from '../hooks/useOrganicOverviewReport';
import useOrganicOverviewMetrics from '../hooks/useOrganicOverviewMetrics';

/**
 * Totals of clicks, impressions, CTR and average position for the selected date range compared
 * to the comparison period, and a chart of the selected metric over both periods.
 *
 * @param {Object} props React props.
 * @param {string} props.trackEventId Report ID used in tracking events.
 */
const OrganicOverview = ( { trackEventId } ) => {
	const metrics = useOrganicOverviewMetrics();
	const {
		loaded,
		totals,
		intervals,
		comparisonIntervals,
		interval,
		allowedIntervals,
		labels,
	} = useOrganicOverviewReport();

	const comparison = useMemo( () => {
		return { intervals: comparisonIntervals, totals, labels };
	}, [ comparisonIntervals, totals, labels ] );

	return (
		<>
			<SummarySection
				loaded={ loaded }
				metrics={ metrics }
				totals={ totals }
				trackEventId={ trackEventId }
			/>
			<ChartSection
				metrics={ metrics }
				loaded={ loaded }
				intervals={ intervals }
				comparison={ comparison }
				interval={ interval }
				allowedIntervals={ allowedIntervals }
			/>
		</>
	);
};

export default OrganicOverview;

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
import { TRACK_EVENT_ID } from '../constants';

/**
 * Totals of clicks, impressions, CTR and average position for the selected date range compared
 * to the comparison period, and a chart of the selected metric over both periods.
 */
const OrganicOverview = () => {
	const metrics = useOrganicOverviewMetrics();
	const {
		hasFinishedResolution,
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
				loaded={ hasFinishedResolution }
				metrics={ metrics }
				totals={ totals }
				trackEventId={ TRACK_EVENT_ID }
			/>
			<ChartSection
				metrics={ metrics }
				loaded={ hasFinishedResolution }
				intervals={ intervals }
				comparison={ comparison }
				interval={ interval }
				allowedIntervals={ allowedIntervals }
			/>
		</>
	);
};

export default OrganicOverview;

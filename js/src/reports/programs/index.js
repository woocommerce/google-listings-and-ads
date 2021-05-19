/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import isWCNavigationEnabled from '.~/utils/isWCNavigationEnabled';
import useProgramsReport from './useProgramsReport';
import TabNav from '../../tab-nav';
import SubNav from '../sub-nav';
import ProgramsReportFilters from './programs-report-filters';
import SummarySection from '../summary-section';
import ChartSection from '../chart-section';
import CompareProgramsTableCard from './compare-programs-table-card';
import '../../dashboard/index.scss';

/**
 * Available metrics and their human-readable labels.
 *
 * @type {Array<import('../index.js').Metric>}
 */
const performanceMetrics = [
	{
		key: 'sales',
		label: __( 'Total Sales', 'google-listings-and-ads' ),
		isCurrency: true,
	},
	{
		key: 'conversions',
		label: __( 'Conversions', 'google-listings-and-ads' ),
	},
	{
		key: 'clicks',
		label: __( 'Clicks', 'google-listings-and-ads' ),
	},
	{
		key: 'impressions',
		label: __( 'Impressions', 'google-listings-and-ads' ),
	},
	{
		key: 'spend',
		label: __( 'Total Spend', 'google-listings-and-ads' ),
		isCurrency: true,
	},
];

const ProgramsReport = () => {
	const navigationEnabled = isWCNavigationEnabled();
	const reportId = 'reports-programs';

	// Only after calling the API we would know if the default "All listings" includes or not any paid listings.
	const {
		loaded,
		data: { totals, intervals },
	} = useProgramsReport();

	// Show only available data.
	// Until ~Q4 2021, free listings, may not have all metrics.
	const availableMetrics = performanceMetrics.filter(
		( { key } ) => totals[ key ]
	);

	return (
		<div className="gla-dashboard">
			{ ! navigationEnabled && <TabNav /> }
			<SubNav initialName="programs" />

			<ProgramsReportFilters query={ getQuery() } report={ reportId } />
			<div className="gla-reports__performance">
				<SummarySection
					loaded={ loaded }
					metrics={ availableMetrics }
					expectedLength={ performanceMetrics.length }
					totals={ totals }
				/>
				<ChartSection
					metrics={ availableMetrics }
					loaded={ loaded }
					intervals={ intervals }
				/>
			</div>
			<div className="gla-dashboard__programs">
				<CompareProgramsTableCard trackEventReportId={ reportId } />
			</div>
		</div>
	);
};

export default ProgramsReport;

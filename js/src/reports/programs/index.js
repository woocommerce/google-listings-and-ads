/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
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
const commonMetrics = [
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
];
const performanceMetrics = [
	...commonMetrics,
	{
		key: 'spend',
		label: __( 'Total Spend', 'google-listings-and-ads' ),
		isCurrency: true,
	},
];
const tableMetrics = [
	...commonMetrics,
	{
		key: 'spend',
		label: __( 'Spend', 'google-listings-and-ads' ),
		isCurrency: true,
	},
];

const ProgramsReport = () => {
	const trackEventId = 'reports-programs';

	// Only after calling the API we would know if the default "All listings" includes or not any paid listings.
	const {
		loaded,
		data: { totals, intervals, freeListings, campaigns },
	} = useProgramsReport();

	// Show only available data.
	// Until ~Q4 2021, free listings, may not have all metrics.
	const availableMetrics = performanceMetrics.filter( ( { key } ) =>
		totals.hasOwnProperty( key )
	);

	// Anticipate all to come, show all column headers if the data is still being loaded.
	const expectedTableMetrics = loaded
		? tableMetrics.filter( ( { key } ) => totals.hasOwnProperty( key ) )
		: tableMetrics;

	return (
		<div className="gla-dashboard">
			<TabNav initialName="reports" />
			<SubNav initialName="programs" />

			<ProgramsReportFilters
				query={ getQuery() }
				trackEventId={ trackEventId }
			/>
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
				<CompareProgramsTableCard
					trackEventReportId={ trackEventId }
					isLoading={ ! loaded }
					metrics={ expectedTableMetrics }
					freeListings={ freeListings }
					campaigns={ campaigns }
				/>
			</div>
		</div>
	);
};

export default ProgramsReport;

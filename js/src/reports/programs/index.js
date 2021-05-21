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
		reportQuery: { fields, orderby, order },
	} = useProgramsReport();

	// Until ~Q4 2021, free listings, may not have all metrics.
	// Anticipate all requested fields to come, show available once loaded.
	const availableMetrics = loaded
		? performanceMetrics.filter( ( { key } ) =>
				totals.hasOwnProperty( key )
		  )
		: performanceMetrics.filter( ( { key } ) => fields.includes( key ) );

	const expectedTableMetrics = loaded
		? tableMetrics.filter( ( { key } ) => totals.hasOwnProperty( key ) )
		: tableMetrics.filter( ( { key } ) => fields.includes( key ) );

	return (
		<>
			<TabNav initialName="reports" />
			<SubNav initialName="programs" />

			<ProgramsReportFilters
				query={ getQuery() }
				trackEventId={ trackEventId }
			/>
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
			<CompareProgramsTableCard
				trackEventReportId={ trackEventId }
				isLoading={ ! loaded }
				orderby={ orderby }
				order={ order }
				metrics={ expectedTableMetrics }
				freeListings={ freeListings }
				campaigns={ campaigns }
			/>
		</>
	);
};

export default ProgramsReport;

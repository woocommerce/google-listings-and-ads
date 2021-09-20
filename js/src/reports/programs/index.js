/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useProgramsReport, { usePerformanceReport } from './useProgramsReport';
import DifferentCurrencyNotice from '.~/components/different-currency-notice';
import NavigationClassic from '.~/components/navigation-classic';
import ProgramsReportFilters from './programs-report-filters';
import SummarySection from '../summary-section';
import ChartSection from '../chart-section';
import CompareProgramsTableCard from './compare-programs-table-card';
import ReportsNavigation from '../reports-navigation';
import { formatCurrencyCell, formatNumericCell } from '../format-amount';

/**
 * Available metrics and their human-readable labels.
 *
 * @type {Array<import('../index.js').Metric>}
 */
const commonMetrics = [
	{
		key: 'sales',
		label: __( 'Total Sales', 'google-listings-and-ads' ),
		formatFn: formatCurrencyCell,
		isCurrency: true,
	},
	{
		key: 'conversions',
		label: __( 'Conversions', 'google-listings-and-ads' ),
		formatFn: formatNumericCell,
	},
	{
		key: 'clicks',
		label: __( 'Clicks', 'google-listings-and-ads' ),
		formatFn: formatNumericCell,
	},
	{
		key: 'impressions',
		label: __( 'Impressions', 'google-listings-and-ads' ),
		formatFn: formatNumericCell,
	},
];
const performanceMetrics = [
	...commonMetrics,
	{
		key: 'spend',
		label: __( 'Total Spend', 'google-listings-and-ads' ),
		formatFn: formatCurrencyCell,
		isCurrency: true,
	},
];
const tableMetrics = [
	...commonMetrics,
	{
		key: 'spend',
		label: __( 'Spend', 'google-listings-and-ads' ),
		formatFn: formatCurrencyCell,
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

	const hasFieldInResults = loaded && Object.keys( totals ).length > 0;
	// Until ~Q4 2021, free listings, may not have all metrics.
	// Anticipate all requested fields to come, show available once loaded.
	const availableMetrics = hasFieldInResults
		? performanceMetrics.filter( ( { key } ) =>
				totals.hasOwnProperty( key )
		  )
		: performanceMetrics.filter( ( { key } ) => fields.includes( key ) );

	const expectedTableMetrics = hasFieldInResults
		? tableMetrics.filter( ( { key } ) => totals.hasOwnProperty( key ) )
		: tableMetrics.filter( ( { key } ) => fields.includes( key ) );

	const {
		loaded: performanceLoaded,
		data: performanceTotals,
	} = usePerformanceReport( totals );
	// Use 'primary' data before the previous period is loaded.
	const availablePerformance = performanceLoaded ? performanceTotals : totals;

	return (
		<>
			<DifferentCurrencyNotice context={ trackEventId } />
			<NavigationClassic />
			<ReportsNavigation />
			<ProgramsReportFilters
				query={ getQuery() }
				trackEventId={ trackEventId }
			/>
			<SummarySection
				loaded={ loaded }
				metrics={ availableMetrics }
				expectedLength={ performanceMetrics.length }
				totals={ availablePerformance }
				trackEventId={ trackEventId }
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

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Chart } from '@woocommerce/components';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useProgramsReport from './useProgramsReport';
import TabNav from '../../tab-nav';
import SubNav from '../sub-nav';
import ProgramsReportFilters from './programs-report-filters';
import SummarySection from '../summary-section';
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
		label: __( 'Net Sales', 'google-listings-and-ads' ),
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
	const reportId = 'reports-programs';

	// Only after calling the API we would know if the default "All listings" includes or not any paid listings.
	const {
		loaded,
		data: { totals, intervals },
	} = useProgramsReport();

	const chartData = [
		{
			date: '2018-05-30T00:00:00',
			Conversions: {
				label: 'Conversions',
				value: 14205,
			},
		},
		{
			date: '2018-05-31T00:00:00',
			Conversions: {
				label: 'Conversions',
				value: 10581,
			},
		},
		{
			date: '2018-06-01T00:00:00',
			Conversions: {
				label: 'Conversions',
				value: 16307,
			},
		},
		{
			date: '2018-06-02T00:00:00',
			Conversions: {
				label: 'Conversions',
				value: 13481,
			},
		},
		{
			date: '2018-06-03T00:00:00',
			Conversions: {
				label: 'Conversions',
				value: 10581,
			},
		},
		{
			date: '2018-06-04T00:00:00',
			Conversions: {
				label: 'Conversions',
				value: 19874,
			},
		},
		{
			date: '2018-06-05T00:00:00',
			Conversions: {
				label: 'Conversions',
				value: 20593,
			},
		},
	];

	// Show only available data.
	// Until ~Q4 2021, free listings, may not have all metrics.
	const availableMetrics = performanceMetrics.filter(
		( { key } ) => totals[ key ]
	);

	return (
		<div className="gla-dashboard">
			<TabNav initialName="reports" />
			<SubNav initialName="programs" />

			<ProgramsReportFilters query={ getQuery() } report={ reportId } />
			<div className="gla-reports__performance">
				<SummarySection
					loaded={ loaded }
					metrics={ availableMetrics }
					expectedLength={ performanceMetrics.length }
					totals={ totals }
				/>
				<Chart
					data={ chartData }
					title="Conversions"
					layout="time-comparison"
					interactiveLegend="false"
					showHeaderControls="false"
				/>
			</div>
			<div className="gla-dashboard__programs">
				<CompareProgramsTableCard trackEventReportId={ reportId } />
			</div>
		</div>
	);
};

export default ProgramsReport;

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryList, Chart, FilterPicker } from '@woocommerce/components';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import AppDateRangeFilterPicker from '../dashboard/app-date-range-filter-picker';
import CompareProgramsTableCard from './compare-programs-table-card';
import MetricNumber from './metric-number';
import '../dashboard/index.scss';
import { programsFilterConfig } from './filter-config';
import metricsData from './mocked-metrics-data'; // Mocked data

/**
 * Available metrics and their human-readable labels.
 *
 * @type {Map<string, string>}
 */
const performanceMetrics = [
	[ 'netSales', __( 'Net Sales', 'google-listings-and-ads' ) ],
	[ 'itemsSold', __( 'Items Sold', 'google-listings-and-ads' ) ],
	[ 'conversions', __( 'Conversions', 'google-listings-and-ads' ) ],
	[ 'clicks', __( 'Clicks', 'google-listings-and-ads' ) ],
	[ 'impressions', __( 'Impressions', 'google-listings-and-ads' ) ],
	[ 'totalSpend', __( 'Total Spend', 'google-listings-and-ads' ) ],
];

const ProgramsReports = () => {
	// TODO: this data should come from backend API.
	const data = metricsData();

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
		( [ key ] ) => data[ key ]
	);

	const trackEventReportId = 'reports-programs';

	return (
		<div className="gla-dashboard">
			<TabNav initialName="reports" />
			<div className="gla-dashboard__filter">
				<AppDateRangeFilterPicker
					trackEventReportId={ trackEventReportId }
				/>
				<FilterPicker
					config={ programsFilterConfig }
					query={ getQuery() }
				/>
			</div>
			<div className="gla-reports__performance">
				<SummaryList>
					{ () =>
						availableMetrics.map( ( [ key, label ] ) => (
							<MetricNumber
								key={ key }
								label={ label }
								data={ data[ key ] }
							/>
						) )
					}
				</SummaryList>
				<Chart
					data={ chartData }
					title="Conversions"
					layout="time-comparison"
					interactiveLegend="false"
					showHeaderControls="false"
				/>
			</div>
			<div className="gla-dashboard__programs">
				<CompareProgramsTableCard
					trackEventReportId={ trackEventReportId }
				/>
			</div>
		</div>
	);
};

export default ProgramsReports;

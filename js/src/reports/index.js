/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	SummaryList,
	SummaryNumber,
	Chart,
	FilterPicker,
} from '@woocommerce/components';
import { Tooltip } from '@wordpress/components';
import GridiconInfoOutline from 'gridicons/dist/info-outline';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import AppDateRangeFilterPicker from '../dashboard/app-date-range-filter-picker';
import CompareProgramsTableCard from './compare-programs-table-card';
import '../dashboard/index.scss';
import './index.scss';
import { programsFilterConfig } from './filter-config';
import metricsData from './mocked-metrics-data'; // Mocked data

/**
 * Available metrics and their human-readable labels.
 *
 * @type {Map<string, string>}
 */
const performanceMetrics = new Map( [
	[ 'netSales', __( 'Net Sales', 'google-listings-and-ads' ) ],
	[ 'itemsSold', __( 'Items Sold', 'google-listings-and-ads' ) ],
	[ 'conversions', __( 'Conversions', 'google-listings-and-ads' ) ],
	[ 'clicks', __( 'Clicks', 'google-listings-and-ads' ) ],
	[ 'impressions', __( 'Impressions', 'google-listings-and-ads' ) ],
	[ 'totalSpend', __( 'Total Spend', 'google-listings-and-ads' ) ],
] );

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
					{ () => {
						return Array.from(
							performanceMetrics,
							function renderSingleMetric( [ key, label ] ) {
								// Show only available data.
								// Until ~Q4 2021, free listings, may not have all metrics.
								if ( data[ key ] ) {
									let markedLabel = label;
									// Until ~Q4 2021, metrics for all programs, may lack data for free listings.
									if ( data[ key ].missingFreeListingsData ) {
										const infoText = __(
											'This data is available for paid campaigns only.',
											'google-listings-and-ads'
										);
										markedLabel = (
											<div className="gla-reports__metric-label">
												{ label }
												<Tooltip text={ infoText }>
													<span>
														<GridiconInfoOutline
															className="gla-reports__metric-infoicon"
															role="img"
															aria-label={
																infoText
															}
															size={ 16 }
														/>
													</span>
												</Tooltip>
											</div>
										);
									}
									return (
										<SummaryNumber
											key={ key }
											label={ markedLabel }
											value={ data[ key ].value }
											delta={ data[ key ].delta }
										/>
									);
								}
								return undefined;
							}
						).filter( Boolean ); // Ignore undefined elements.
					} }
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

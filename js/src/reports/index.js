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
	const data = {
		conversions: {
			value: '4,102',
			delta: -2.21,
			label: 'Conversions',
			missingFreeListingsData: true,
		},
		clicks: {
			value: '14,135',
			delta: 0,
			label: 'Clicks',
		},
		impressions: {
			value: '383,512',
			delta: 1.28,
			label: 'Impressions',
		},
		itemsSold: {
			value: '6,928',
			delta: 0.35,
			label: 'itemsSold',
			missingFreeListingsData: true,
		},
		netSales: {
			value: '$10,802.93',
			delta: 5.35,
			label: 'Net Sales',
			missingFreeListingsData: true,
		},
		totalSpend: {
			value: '$600.00',
			delta: -1.97,
			label: 'Total Spend',
		},
	};

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

	const programsFilterConfig = {
		label: __( 'Show', 'google-listings-and-ads' ),
		staticParams: [ 'period' ],
		param: 'programs',
		filters: [
			{
				label: __( 'All Google programs', 'google-listings-and-ads' ),
				value: 'all',
			},
			{
				label: __( 'Single program', 'google-listings-and-ads' ),
				value: 'single',
			},
			{
				label: __( 'Comparison', 'google-listings-and-ads' ),
				value: 'comparison',
			},
		],
	};

	return (
		<div className="gla-dashboard">
			<TabNav initialName="reports" />
			<div className="gla-dashboard__filter">
				<AppDateRangeFilterPicker />
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
							( [ key, label ] ) => {
								// Show only available data.
								// Until ~Q4 2021, free listings, may not have all metrics.
								if( data[ key ] ){
									let markedLabel = label;
									// Until ~Q4 2021, metrics for all programs, may lack data for free listings.
									if( data[ key ].missingFreeListingsData ) {
										const infoText =  __(
											'This data is available for paid campaigns only.',
											'google-listings-and-ads'
										);
										markedLabel = (
											<div className="gla-reports__metric-label">
												{ label }
												<Tooltip
													text={ infoText }
												>
													<span>
														<GridiconInfoOutline
															className="gla-reports__metric-infoicon"
															role="img"
															aria-label={ infoText }
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
							}
						).filter( e => e ); // Ignore undefined elements.
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
				<CompareProgramsTableCard />
			</div>
		</div>
	);
};

export default ProgramsReports;

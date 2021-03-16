/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryNumber, Link } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import TabNav from '../tab-nav';
import AppDateRangeFilterPicker from './app-date-range-filter-picker';
import SummaryCard from './summary-card';
import PaidCampaignPromotionCard from './paid-campaign-promotion-card';
import CampaignCreationSuccessGuide from './campaign-creation-success-guide';
import AllProgramsTableCard from './all-programs-table-card';
import './index.scss';

const Dashboard = () => {
	const { adsSetupComplete } = glaData;
	// TODO: this data should come from backend API.
	const data = {
		freeListing: {
			netSales: {
				value: '$1203.58',
				delta: 50,
			},
			totalSpend: {
				value: 'Free',
				delta: null,
			},
		},
		paidCampaigns: {
			netSales: {
				value: '$8502.15',
				delta: 3.35,
			},
			totalSpend: {
				value: '$600.00',
				delta: -1.97,
			},
		},
	};
	const trackEventReportId = 'dashboard';
	const paidPerformanceTitle = __(
		'Performance (Paid Campaigns)',
		'woocommerce-admin'
	);

	return (
		<div className="gla-dashboard">
			<TabNav initialName="dashboard" />
			<div className="gla-dashboard__filter">
				<AppDateRangeFilterPicker
					trackEventReportId={ trackEventReportId }
				/>
				<Link href={ getNewPath( null, '/google/reports/programs' ) }>
					<Button isPrimary>View Reports</Button>
				</Link>
			</div>
			<div className="gla-dashboard__performance">
				<SummaryCard
					title={ __(
						'Performance (Free Listing)',
						'woocommerce-admin'
					) }
				>
					<SummaryNumber
						label={ __( 'Net Sales', 'google-listings-and-ads' ) }
						value={ data.freeListing.netSales.value }
						delta={ data.freeListing.netSales.delta }
					/>
					<SummaryNumber
						label={ __( 'Total Spend', 'google-listings-and-ads' ) }
						value={ data.freeListing.totalSpend.value }
						delta={ data.freeListing.totalSpend.delta }
					/>
				</SummaryCard>
				{ adsSetupComplete ? (
					<SummaryCard title={ paidPerformanceTitle }>
						<SummaryNumber
							label={ __(
								'Net Sales',
								'google-listings-and-ads'
							) }
							value={ data.paidCampaigns.netSales.value }
							delta={ data.paidCampaigns.netSales.delta }
						/>
						<SummaryNumber
							label={ __(
								'Total Spend',
								'google-listings-and-ads'
							) }
							value={ data.paidCampaigns.totalSpend.value }
							delta={ data.paidCampaigns.totalSpend.delta }
						/>
					</SummaryCard>
				) : (
					<PaidCampaignPromotionCard title={ paidPerformanceTitle } />
				) }
			</div>
			<div className="gla-dashboard__programs">
				<CampaignCreationSuccessGuide />
				<AllProgramsTableCard
					trackEventReportId={ trackEventReportId }
				/>
			</div>
		</div>
	);
};

export default Dashboard;

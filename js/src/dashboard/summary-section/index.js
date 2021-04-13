/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryNumber } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppSpinner from '.~/components/app-spinner';
import SummaryCard from './summary-card';
import PaidCampaignPromotionCard from './paid-campaign-promotion-card';
import './index.scss';

const paidPerformanceTitle = __(
	'Performance (Paid Campaigns)',
	'woocommerce-admin'
);

const FreePerformanceCard = () => {
	// TODO: loading and data should come from backend API.
	const loading = false;
	const data = {
		freeListing: {
			clicks: {
				value: '$1203.58',
				delta: 50,
			},
		},
	};

	return (
		<SummaryCard
			title={ __( 'Performance (Free Listing)', 'woocommerce-admin' ) }
		>
			{ loading ? (
				<AppSpinner />
			) : (
				[
					<SummaryNumber
						key="1"
						label={ __( 'Clicks', 'google-listings-and-ads' ) }
						value={ data.freeListing.clicks.value }
						delta={ data.freeListing.clicks.delta }
					/>,
					<SummaryNumber
						key="2"
						label={ __( 'Total Spend', 'google-listings-and-ads' ) }
						value={ __( 'Free', 'google-listings-and-ads' ) }
						delta={ null }
					/>,
				]
			) }
		</SummaryCard>
	);
};

const PaidPerformanceCard = () => {
	// TODO: loading and data should come from backend API.
	const loading = false;
	const data = {
		paidCampaigns: {
			sales: {
				value: '$8502.15',
				delta: 3.35,
			},
			spend: {
				value: '$600.00',
				delta: -1.97,
			},
		},
	};

	return (
		<SummaryCard title={ paidPerformanceTitle }>
			{ loading ? (
				<AppSpinner />
			) : (
				[
					<SummaryNumber
						key="1"
						label={ __( 'Total Sales', 'google-listings-and-ads' ) }
						value={ data.paidCampaigns.sales.value }
						delta={ data.paidCampaigns.sales.delta }
					/>,
					<SummaryNumber
						key="2"
						label={ __( 'Total Spend', 'google-listings-and-ads' ) }
						value={ data.paidCampaigns.spend.value }
						delta={ data.paidCampaigns.spend.delta }
					/>,
				]
			) }
		</SummaryCard>
	);
};

export default function SummarySection() {
	const { adsSetupComplete } = glaData;

	return (
		<>
			<FreePerformanceCard />
			{ adsSetupComplete ? (
				<PaidPerformanceCard />
			) : (
				<PaidCampaignPromotionCard title={ paidPerformanceTitle } />
			) }
		</>
	);
}

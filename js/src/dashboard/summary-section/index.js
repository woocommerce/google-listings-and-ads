/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryNumber } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import SummaryCard from './summary-card';
import PaidCampaignPromotionCard from './paid-campaign-promotion-card';
import './index.scss';

export default function SummarySection() {
	// TODO: this data should come from backend API.
	//       And also, reconsider that would it better to encapsulate the `adsSetupComplete` and
	//       `paidPerformanceTitle` into a new component and conditionally render different content.
	const { adsSetupComplete } = glaData;
	const paidPerformanceTitle = __(
		'Performance (Paid Campaigns)',
		'woocommerce-admin'
	);
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

	return (
		<>
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
						label={ __( 'Net Sales', 'google-listings-and-ads' ) }
						value={ data.paidCampaigns.netSales.value }
						delta={ data.paidCampaigns.netSales.delta }
					/>
					<SummaryNumber
						label={ __( 'Total Spend', 'google-listings-and-ads' ) }
						value={ data.paidCampaigns.totalSpend.value }
						delta={ data.paidCampaigns.totalSpend.delta }
					/>
				</SummaryCard>
			) : (
				<PaidCampaignPromotionCard title={ paidPerformanceTitle } />
			) }
		</>
	);
}

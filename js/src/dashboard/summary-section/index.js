/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryNumber } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { glaData, REPORT_SOURCE_PAID, REPORT_SOURCE_FREE } from '.~/constants';
import useCurrencyFormat from '.~/hooks/useCurrencyFormat';
import useCurrencyFactory from '.~/hooks/useCurrencyFactory';
import usePerformance from './usePerformance';
import AppSpinner from '.~/components/app-spinner';
import SummaryCard from './summary-card';
import PaidCampaignPromotionCard from './paid-campaign-promotion-card';
import './index.scss';

const paidPerformanceTitle = __(
	'Performance (Paid Campaigns)',
	'google-listings-and-ads'
);

const numberFormatSetting = { precision: 0 };

const FreePerformanceCard = () => {
	const formatNumber = useCurrencyFormat( numberFormatSetting );
	const { data, loaded } = usePerformance( REPORT_SOURCE_FREE );

	return (
		<SummaryCard
			title={ __(
				'Performance (Free Listing)',
				'google-listings-and-ads'
			) }
		>
			{ loaded ? (
				[
					<SummaryNumber
						key="1"
						label={ __( 'Clicks', 'google-listings-and-ads' ) }
						value={ formatNumber( data.clicks.value ) }
						prevValue={ formatNumber( data.clicks.prevValue ) }
						delta={ data.clicks.delta }
					/>,
					<SummaryNumber
						key="2"
						label={ __( 'Total Spend', 'google-listings-and-ads' ) }
						value={ __( 'Free', 'google-listings-and-ads' ) }
						delta={ null }
					/>,
				]
			) : (
				<AppSpinner />
			) }
		</SummaryCard>
	);
};

const PaidPerformanceCard = () => {
	const { formatAmount } = useCurrencyFactory();
	const { data, loaded } = usePerformance( REPORT_SOURCE_PAID );

	return (
		<SummaryCard title={ paidPerformanceTitle }>
			{ loaded ? (
				[
					<SummaryNumber
						key="1"
						label={ __( 'Total Sales', 'google-listings-and-ads' ) }
						value={ formatAmount( data.sales.value ) }
						prevValue={ formatAmount( data.sales.prevValue ) }
						delta={ data.sales.delta }
					/>,
					<SummaryNumber
						key="2"
						label={ __( 'Total Spend', 'google-listings-and-ads' ) }
						value={ formatAmount( data.spend.value ) }
						prevValue={ formatAmount( data.spend.prevValue ) }
						delta={ data.spend.delta }
					/>,
				]
			) : (
				<AppSpinner />
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

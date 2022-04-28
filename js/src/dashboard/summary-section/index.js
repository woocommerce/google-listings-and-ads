/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryNumber } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { glaData, REPORT_SOURCE_PAID, REPORT_SOURCE_FREE } from '.~/constants';
import useAdsCurrency from '.~/hooks/useAdsCurrency';
import useCurrencyFormat from '.~/hooks/useCurrencyFormat';
import usePerformance from './usePerformance';
import PerformanceCard from './performance-card';
import SummaryCard from './summary-card';
import PaidCampaignPromotionCard from './paid-campaign-promotion-card';

const numberFormatSetting = { precision: 0 };
/**
 * @fires gla_google_mc_link_click with `{ context: 'dashboard' }`
 */
const FreePerformanceCard = () => {
	const formatNumber = useCurrencyFormat( numberFormatSetting );
	const { data, loaded } = usePerformance( REPORT_SOURCE_FREE );

	return (
		<PerformanceCard
			loaded={ loaded }
			data={ data }
			noDataMessage={ {
				body: __(
					"We're having trouble loading this data. Try again later, or track your performance in Google Merchant Center.",
					'google-listings-and-ads'
				),
				link: 'https://merchants.google.com/mc/reporting/dashboard',
				eventName: 'gla_google_mc_link_click',
				buttonLabel: __(
					'Open Google Merchant Center',
					'google-listings-and-ads'
				),
			} }
		>
			{ ( loadedData ) => [
				<SummaryNumber
					key="1"
					label={ __( 'Clicks', 'google-listings-and-ads' ) }
					value={ formatNumber( loadedData.clicks.value ) }
					prevValue={ formatNumber( loadedData.clicks.prevValue ) }
					delta={ loadedData.clicks.delta }
				/>,
				<SummaryNumber
					key="2"
					label={ __( 'Total Spend', 'google-listings-and-ads' ) }
					value={ __( 'Free', 'google-listings-and-ads' ) }
					delta={ null }
				/>,
			] }
		</PerformanceCard>
	);
};

const PaidPerformanceCard = () => {
	// The amount of Total Sales and Total Spend are given in the Ads' currency.
	// We use currency code to make sure it's nonambiguous.
	const { formatAmount } = useAdsCurrency();
	const { data, loaded } = usePerformance( REPORT_SOURCE_PAID );

	return (
		<PerformanceCard
			loaded={ loaded }
			data={ data }
			noDataMessage={ {
				body: __(
					"We're having trouble loading this data. Try again later, or track your performance in Google Ads.",
					'google-listings-and-ads'
				),
				link: 'https://ads.google.com/',
				eventName: 'gla_google_ads_link_click',
				buttonLabel: __( 'Open Google Ads', 'google-listings-and-ads' ),
			} }
		>
			{ ( loadedData ) => [
				<SummaryNumber
					key="1"
					label={ __( 'Total Sales', 'google-listings-and-ads' ) }
					value={ formatAmount( loadedData.sales.value, true ) }
					prevValue={ formatAmount(
						loadedData.sales.prevValue,
						true
					) }
					delta={ loadedData.sales.delta }
				/>,
				<SummaryNumber
					key="2"
					label={ __( 'Total Spend', 'google-listings-and-ads' ) }
					value={ formatAmount( loadedData.spend.value, true ) }
					prevValue={ formatAmount(
						loadedData.spend.prevValue,
						true
					) }
					delta={ loadedData.spend.delta }
				/>,
			] }
		</PerformanceCard>
	);
};

export default function SummarySection() {
	const { adsSetupComplete } = glaData;

	return (
		<>
			<SummaryCard
				title={ __(
					'Performance (Free Listing)',
					'google-listings-and-ads'
				) }
			>
				<FreePerformanceCard />
			</SummaryCard>
			<SummaryCard
				title={ __(
					'Performance (Paid Campaigns)',
					'google-listings-and-ads'
				) }
			>
				{ adsSetupComplete ? (
					<PaidPerformanceCard />
				) : (
					<PaidCampaignPromotionCard />
				) }
			</SummaryCard>
		</>
	);
}

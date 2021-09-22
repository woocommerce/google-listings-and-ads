/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryNumber } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { glaData, REPORT_SOURCE_PAID, REPORT_SOURCE_FREE } from '.~/constants';
import { useAdsCurrencyConfig } from '.~/hooks/useAdsCurrency';
import formatAmountWithCode from '.~/utils/formatAmountWithCode';
import useCurrencyFormat from '.~/hooks/useCurrencyFormat';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import usePerformance from './usePerformance';
import PerformanceCard from './performance-card';
import PaidCampaignPromotionCard from './paid-campaign-promotion-card';

const paidPerformanceTitle = __(
	'Performance (Paid Campaigns)',
	'google-listings-and-ads'
);

const numberFormatSetting = { precision: 0 };

const FreePerformanceCard = () => {
	const formatNumber = useCurrencyFormat( numberFormatSetting );
	const { data, loaded } = usePerformance( REPORT_SOURCE_FREE );

	return (
		<PerformanceCard
			title={ __(
				'Performance (Free Listing)',
				'google-listings-and-ads'
			) }
			loaded={ loaded }
			data={ data }
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
	// Spend amount is given in the Ads' currency, but Total Sales is in store's currency.
	// We use codes to make sure it's nonambiguous.
	// Use just `useAdsCurrency`'s/`getCurrencyConfig`'s  `formatAmount`s once https://github.com/woocommerce/woocommerce-admin/pull/7575 is released and accessible.
	const { currencyConfig: adsCurrency } = useAdsCurrencyConfig();
	const storeCurrencyConfig = useStoreCurrency();
	const { data, loaded } = usePerformance( REPORT_SOURCE_PAID );

	return (
		<PerformanceCard
			title={ paidPerformanceTitle }
			loaded={ loaded }
			data={ data }
		>
			{ ( loadedData ) => [
				<SummaryNumber
					key="1"
					label={ __( 'Total Sales', 'google-listings-and-ads' ) }
					value={ formatAmountWithCode(
						storeCurrencyConfig,
						loadedData.sales.value
					) }
					prevValue={ formatAmountWithCode(
						storeCurrencyConfig,
						loadedData.sales.prevValue
					) }
					delta={ loadedData.sales.delta }
				/>,
				<SummaryNumber
					key="2"
					label={ __( 'Total Spend', 'google-listings-and-ads' ) }
					value={ formatAmountWithCode(
						adsCurrency,
						loadedData.spend.value
					) }
					prevValue={ formatAmountWithCode(
						adsCurrency,
						loadedData.spend.prevValue
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
			<FreePerformanceCard />
			{ adsSetupComplete ? (
				<PaidPerformanceCard />
			) : (
				<PaidCampaignPromotionCard title={ paidPerformanceTitle } />
			) }
		</>
	);
}

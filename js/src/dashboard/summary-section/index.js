/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SummaryNumber } from '@woocommerce/components';
import CurrencyFactory from '@woocommerce/currency';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import { glaData, REPORT_SOURCE_PAID, REPORT_SOURCE_FREE } from '.~/constants';
import usePerformance from './usePerformance';
import PerformanceCard from './performance-card';
import PaidCampaignPromotionCard from './paid-campaign-promotion-card';

const paidPerformanceTitle = __(
	'Performance (Paid Campaigns)',
	'google-listings-and-ads'
);

// Note: Since the `delta` prop of SummaryNumber component wouldn't apply the WC Settings' currency options,
//       it uses '.' as the decimal separator when transforming to percentage format.
//       In order to keep the format consistency of number and currency in the same block,
//       the WC Settings' currency options are not applied in this SummarySection component.
// ref: https://github.com/woocommerce/woocommerce-admin/blob/v1.6.0/packages/components/src/summary/number.js#L133-L136
const formatNumber = ( number ) => numberFormat( { precision: 0 }, number );
const { formatAmount } = CurrencyFactory();

const FreePerformanceCard = () => {
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
					value={ formatAmount( loadedData.sales.value ) }
					prevValue={ formatAmount( loadedData.sales.prevValue ) }
					delta={ loadedData.sales.delta }
				/>,
				<SummaryNumber
					key="2"
					label={ __( 'Total Spend', 'google-listings-and-ads' ) }
					value={ formatAmount( loadedData.spend.value ) }
					prevValue={ formatAmount( loadedData.spend.prevValue ) }
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

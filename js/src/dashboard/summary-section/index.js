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
import { glaData } from '.~/constants';
import usePerformance from './usePerformance';
import AppSpinner from '.~/components/app-spinner';
import SummaryCard from './summary-card';
import PaidCampaignPromotionCard from './paid-campaign-promotion-card';
import './index.scss';

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
	const { data, loading } = usePerformance( 'free' );

	return (
		<SummaryCard
			title={ __(
				'Performance (Free Listing)',
				'google-listings-and-ads'
			) }
		>
			{ loading ? (
				<AppSpinner />
			) : (
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
			) }
		</SummaryCard>
	);
};

const PaidPerformanceCard = () => {
	const { data, loading } = usePerformance( 'paid' );

	return (
		<SummaryCard title={ paidPerformanceTitle }>
			{ loading ? (
				<AppSpinner />
			) : (
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

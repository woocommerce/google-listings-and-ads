/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const TitleDescription = ( { title, description } ) => (
	<>
		<h3 className="gla-analytics-overview-promo__title">{ title }</h3>
		<p className="gla-analytics-overview-promo__description">
			{ description }
		</p>
	</>
);

/**
 * Renders the promo title and description for a given metrics case and Google Ads readiness state.
 *
 * @param {Object}  props
 * @param {string}  props.metricsCase      'revenue' or 'products'.
 * @param {boolean} props.isGoogleAdsReady Whether the merchant's Google Ads account is connected, claimed, and granted access.
 * @return {JSX.Element|null} The title/description pair, or `null` when `metricsCase` isn't recognized.
 */
const PromoText = ( { metricsCase, isGoogleAdsReady } ) => {
	if ( metricsCase === 'revenue' && ! isGoogleAdsReady ) {
		return (
			<TitleDescription
				title={ __(
					'Sales a bit slow? Reach more shoppers with Google.',
					'google-listings-and-ads'
				) }
				description={ __(
					'Sync your catalog with Google and grow back your sales by reaching new shoppers right when they are searching to buy.',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	if ( metricsCase === 'revenue' && isGoogleAdsReady ) {
		return (
			<TitleDescription
				title={ __(
					'Sales a bit slow? Give your products a boost with Google.',
					'google-listings-and-ads'
				) }
				description={ __(
					'Launch a Google Ads campaign and grow back your sales by reaching shoppers who are ready to buy.',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	if ( metricsCase === 'products' && ! isGoogleAdsReady ) {
		return (
			<TitleDescription
				title={ __(
					'Selling fewer items than usual? Reach more shoppers with Google.',
					'google-listings-and-ads'
				) }
				description={ __(
					'Sync your catalog with Google and sell more of your products by reaching new shoppers right when they are searching to buy.',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	if ( metricsCase === 'products' && isGoogleAdsReady ) {
		return (
			<TitleDescription
				title={ __(
					'Selling fewer items than usual? Give your products a boost with Google.',
					'google-listings-and-ads'
				) }
				description={ __(
					'Launch a Google Ads campaign and sell more of your products by reaching shoppers who are ready to buy.',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	return null;
};

export default PromoText;

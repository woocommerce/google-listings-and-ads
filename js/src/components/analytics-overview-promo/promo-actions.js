/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import AppButton from '~/components/app-button';
import {
	addReferrerParams,
	getCreateCampaignUrl,
	getSetupAdsUrl,
} from '~/utils/urls';
import { REFERRER_TYPE_ANALYTICS_IN_PRODUCT_PLACEMENTS } from '~/utils/tracks';
import {
	ANALYTICS_OVERVIEW_PROMO_CONTEXT,
	ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
} from './constants';

const SETUP_ADS_URL = getSetupAdsUrl();
const CREATE_CAMPAIGN_URL = getCreateCampaignUrl();

/**
 * The "Get started" CTA is clicked (merchant not yet onboarded).
 *
 * @event gla_analytics_in_product_placements_get_started_click
 * @property {string} context Where the placement is shown.
 * @property {string} case Which metrics-down case matched, `'sales_orders'` or `'products_sold'`.
 */

/**
 * The "Launch a campaign" CTA is clicked (merchant already connected).
 *
 * @event gla_analytics_in_product_placements_launch_campaign_click
 * @property {string} context Where the placement is shown.
 * @property {string} case Which metrics-down case matched, `'sales_orders'` or `'products_sold'`.
 */

/**
 * The placement is dismissed.
 *
 * @event gla_analytics_in_product_placements_dismiss
 * @property {string} context Where the placement is shown.
 * @property {string} case Which metrics-down case matched, `'sales_orders'` or `'products_sold'`.
 */

/**
 * Renders the promo's CTA and Dismiss buttons for a given Google Ads readiness state.
 *
 * @fires gla_analytics_in_product_placements_get_started_click
 * @fires gla_analytics_in_product_placements_launch_campaign_click
 * @fires gla_analytics_in_product_placements_dismiss
 *
 * @param {Object}  props
 * @param {boolean} props.isGoogleAdsReady Whether the merchant's Google Ads account is connected, claimed, and granted access.
 * @param {string}  [props.trackingCase]   Which metrics-down case matched, `'sales_orders'` or `'products_sold'`, for tracking.
 * @return {JSX.Element} The CTA and Dismiss buttons.
 */
const PromoActions = ( { isGoogleAdsReady, trackingCase } ) => {
	const { set } = useDispatch( preferencesStore );

	/**
	 * Handles the dismissal of the promo.
	 */
	const handleDismiss = () => {
		set(
			PREFERENCES_STORE_NAMESPACE,
			ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
			true
		);
	};

	return (
		<Flex
			className="gla-analytics-overview-promo__actions"
			justify="flex-start"
			gap={ 2 }
			wrap
		>
			<FlexItem>
				<AppButton
					variant="primary"
					href={ addReferrerParams(
						isGoogleAdsReady ? CREATE_CAMPAIGN_URL : SETUP_ADS_URL,
						REFERRER_TYPE_ANALYTICS_IN_PRODUCT_PLACEMENTS,
						ANALYTICS_OVERVIEW_PROMO_CONTEXT
					) }
					eventName={
						isGoogleAdsReady
							? 'gla_analytics_in_product_placements_launch_campaign_click'
							: 'gla_analytics_in_product_placements_get_started_click'
					}
					eventProps={ {
						context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
						case: trackingCase,
					} }
				>
					{ isGoogleAdsReady
						? __( 'Launch a campaign', 'google-listings-and-ads' )
						: __( 'Get started', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexItem>
			<FlexItem>
				<AppButton
					variant="secondary"
					onClick={ handleDismiss }
					eventName="gla_analytics_in_product_placements_dismiss"
					eventProps={ {
						context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
						case: trackingCase,
					} }
				>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexItem>
		</Flex>
	);
};

export default PromoActions;

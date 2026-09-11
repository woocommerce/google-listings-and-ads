/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getCreateCampaignUrl, getSetupAdsUrl } from '~/utils/urls';
import { REFERRER_TYPE_IN_PRODUCT_PLACEMENTS } from '~/utils/tracks';
import { ANALYTICS_OVERVIEW_PROMO_CONTEXT } from './constants';

const SETUP_ADS_URL = getSetupAdsUrl();
const CREATE_CAMPAIGN_URL = getCreateCampaignUrl();

/**
 * Appends the placement's referrer info to a CTA href, so the destination
 * flow can attribute its own tracking events back to this placement.
 *
 * @param {string} href Original CTA destination.
 * @return {string} `href` with `referrer_type`/`referrer_id` query params appended.
 */
function withReferrer( href ) {
	return addQueryArgs( href, {
		referrer_type: REFERRER_TYPE_IN_PRODUCT_PLACEMENTS,
		referrer_id: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
	} );
}

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
 * @param {Object}   props
 * @param {boolean}  props.isGoogleAdsReady Whether the merchant's Google Ads account is connected, claimed, and granted access.
 * @param {string}   [props.trackingCase]   Which metrics-down case matched, `'sales_orders'` or `'products_sold'`, for tracking.
 * @param {Function} props.onDismiss        Called when the Dismiss button is clicked.
 * @fires gla_analytics_in_product_placements_get_started_click
 * @fires gla_analytics_in_product_placements_launch_campaign_click
 * @fires gla_analytics_in_product_placements_dismiss
 * @return {JSX.Element} The CTA and Dismiss buttons.
 */
const PromoActions = ( { isGoogleAdsReady, trackingCase, onDismiss } ) => {
	const ctaEventName = isGoogleAdsReady
		? 'gla_analytics_in_product_placements_launch_campaign_click'
		: 'gla_analytics_in_product_placements_get_started_click';

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
					href={ withReferrer(
						isGoogleAdsReady ? CREATE_CAMPAIGN_URL : SETUP_ADS_URL
					) }
					eventName={ ctaEventName }
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
					onClick={ onDismiss }
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

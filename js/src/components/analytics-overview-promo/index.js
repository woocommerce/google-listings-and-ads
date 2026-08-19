/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import {
	Card,
	CardBody,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import usePreference from '~/hooks/usePreference';
import AppButton from '~/components/app-button';
import { getOnboardingUrl, getSetupAdsUrl } from '~/utils/urls';
import {
	recordGlaEvent,
	REFERRER_TYPE_ANALYTICS_IN_PRODUCT_PLACEMENTS,
} from '~/utils/tracks';
import promoImage from '~/images/analytics-promo.png';
import {
	ANALYTICS_OVERVIEW_PROMO_CONTEXT,
	ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
} from './constants';
import './index.scss';

/**
 * Appends the placement's referrer info to a CTA href, so the destination
 * flow can attribute its own tracking events back to this placement.
 *
 * @param {string} href Original CTA destination.
 * @param {string} placementId Placement ID to attribute the referral to.
 * @return {string} `href` with `referrer_type`/`referrer_id` query params appended.
 */
function withReferrer( href, placementId ) {
	return addQueryArgs( href, {
		referrer_type: REFERRER_TYPE_ANALYTICS_IN_PRODUCT_PLACEMENTS,
		referrer_id: placementId,
	} );
}

/**
 * Maps the raw `metricsCase` values GOOWOO-899's `useProductRevenueMetricsDown()`
 * returns (`'revenue'` / `'products'`) to the `case` tracking property values
 * defined by GOOWOO-903 (`'sales_orders'` / `'products_sold'`).
 */
const TRACKING_CASE_BY_MATCHED_CASE = {
	revenue: 'sales_orders',
	products: 'products_sold',
};

/**
 * Analytics overview promo section for the Analytics → Overview page, mounted by the
 * `woocommerce_dashboard_default_sections` filter registered in `~/filters/analytics-overview-section`.
 *
 * @param {string}  matchedCase 'revenue' or 'products'. TODO: (GOOWOO-899): Update
 * @param {boolean} isConnected Whether the merchant is connected (onboarded) to Google for WooCommerce.
 * @return {?Object} `{ title, description, ctaLabel, ctaHref }`, or null when `matchedCase` isn't recognized.
 */
export const getPromoCopy = ( matchedCase, isConnected ) => {
	const ctaLabel = isConnected
		? __( 'Launch a campaign', 'google-listings-and-ads' )
		: __( 'Get started', 'google-listings-and-ads' );
	const ctaHref = isConnected ? getSetupAdsUrl() : getOnboardingUrl();

	switch ( matchedCase ) {
		case 'revenue':
			return {
				title: isConnected
					? __(
							'Sales a bit slow? Give your products a boost with Google.',
							'google-listings-and-ads'
					  )
					: __(
							'Sales a bit slow? Reach more shoppers with Google.',
							'google-listings-and-ads'
					  ),
				description: isConnected
					? __(
							'Launch a Google Ads campaign and grow back your sales by reaching shoppers who are ready to buy.',
							'google-listings-and-ads'
					  )
					: __(
							'Sync your catalog with Google and grow back your sales by reaching new shoppers right when they are searching to buy.',
							'google-listings-and-ads'
					  ),
				ctaLabel,
				ctaHref,
			};

		case 'products':
			return {
				title: isConnected
					? __(
							'Selling fewer items than usual? Give your products a boost with Google.',
							'google-listings-and-ads'
					  )
					: __(
							'Selling fewer items than usual? Reach more shoppers with Google.',
							'google-listings-and-ads'
					  ),
				description: isConnected
					? __(
							'Launch a Google Ads campaign and sell more of your products by reaching shoppers who are ready to buy.',
							'google-listings-and-ads'
					  )
					: __(
							'Sync your catalog with Google and sell more of your products by reaching new shoppers right when they are searching to buy.',
							'google-listings-and-ads'
					  ),
				ctaLabel,
				ctaHref,
			};

		default:
			return null;
	}
};

/**
 * The placement is shown. Re-fires whenever the shown case changes (guarded on
 * `case` + shown-state, not on mount alone), so a date-range switch that hides,
 * re-shows, or swaps the matched case while the section stays mounted is captured.
 *
 * @event gla_analytics_in_product_placements_view
 * @property {string} context Where the placement is shown.
 * @property {string} case Which metrics-down case matched, `'sales_orders'` or `'products_sold'`.
 */

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
 * Promo Card shown on the Analytics → Overview dashboard when a merchant's store metrics
 * are trending down, mounted by the `woocommerce_dashboard_default_sections` filter
 * registered in `~/analytics-overview`.
 *
 * TODO: GOOWOO-899 (metrics-down detection) and GOOWOO-900 (merchant-state gating) are still in
 *
 * @fires gla_analytics_in_product_placements_view
 * @fires gla_analytics_in_product_placements_get_started_click
 * @fires gla_analytics_in_product_placements_launch_campaign_click
 * @fires gla_analytics_in_product_placements_dismiss
 * @return {?JSX.Element} The promo Card, or null when there's nothing to show.
 */
const AnalyticsOverviewPromo = () => {
	const { hasGoogleMCConnection, hasFinishedResolution } =
		useGoogleMCAccount();
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY );

	// TODO: (GOOWOO-899): replace with the matched case from useProductRevenueMetricsDown
	const matchedCase = 'revenue';

	// TODO: (GOOWOO-900): replace with `const { isGoogleAdsReady } = useGoogleAdsAccountReady();`
	const isGoogleAdsReady = hasGoogleMCConnection;

	// TODO: (GOOWOO-900): replace with `const { hasAdSpend } = useHasRecentAdSpend();`
	const hasAdSpend = false;

	const copy = getPromoCopy( matchedCase, isGoogleAdsReady );
	const shouldShow =
		! isDismissed &&
		hasFinishedResolution &&
		! hasAdSpend &&
		Boolean( copy );
	const trackingCase = TRACKING_CASE_BY_MATCHED_CASE[ matchedCase ];

	useEffect( () => {
		if ( shouldShow ) {
			recordGlaEvent( 'gla_analytics_in_product_placements_view', {
				context: ANALYTICS_OVERVIEW_PROMO_CONTEXT,
				case: trackingCase,
			} );
		}
	}, [ trackingCase, shouldShow ] );

	if ( ! shouldShow ) {
		return null;
	}

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

	const ctaEventName = isGoogleAdsReady
		? 'gla_analytics_in_product_placements_launch_campaign_click'
		: 'gla_analytics_in_product_placements_get_started_click';

	return (
		<Card className="gla-analytics-overview-promo">
			<CardBody size="large">
				<Flex align="center" gap={ 8 } justify="flex-start">
					<FlexItem>
						<img
							className="gla-analytics-overview-promo__image"
							src={ promoImage }
							width="136"
							height="116"
							alt={ __(
								'Product displayed across Google with ratings and a growth indicator.',
								'google-listings-and-ads'
							) }
						/>
					</FlexItem>
					<FlexBlock className="gla-analytics-overview-promo__content">
						<Flex direction="column" align="flex-start" gap={ 2 }>
							<FlexBlock>
								<h3 className="gla-analytics-overview-promo__title">
									{ copy.title }
								</h3>
							</FlexBlock>
							<FlexBlock>
								<p className="gla-analytics-overview-promo__description">
									{ copy.description }
								</p>
							</FlexBlock>
							<FlexBlock>
								<Flex gap={ 2 }>
									<FlexItem>
										<AppButton
											variant="primary"
											href={ withReferrer(
												copy.ctaHref,
												ANALYTICS_OVERVIEW_PROMO_CONTEXT
											) }
											eventName={ ctaEventName }
											eventProps={ {
												context:
													ANALYTICS_OVERVIEW_PROMO_CONTEXT,
												case: trackingCase,
											} }
										>
											{ copy.ctaLabel }
										</AppButton>
									</FlexItem>
									<FlexItem>
										<AppButton
											variant="secondary"
											onClick={ handleDismiss }
											eventName="gla_analytics_in_product_placements_dismiss"
											eventProps={ {
												context:
													ANALYTICS_OVERVIEW_PROMO_CONTEXT,
												case: trackingCase,
											} }
										>
											{ __(
												'Dismiss',
												'google-listings-and-ads'
											) }
										</AppButton>
									</FlexItem>
								</Flex>
							</FlexBlock>
						</Flex>
					</FlexBlock>
				</Flex>
			</CardBody>
		</Card>
	);
};

export default AnalyticsOverviewPromo;

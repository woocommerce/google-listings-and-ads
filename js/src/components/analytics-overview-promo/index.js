/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';
import usePreference from '~/hooks/usePreference';
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';
import AppButton from '~/components/app-button';
import { getCreateCampaignUrl, getSetupAdsUrl } from '~/utils/urls';
import promoImage from '~/images/analytics-promo.png';
import {
	ANALYTICS_OVERVIEW_PROMO_CONTEXT,
	ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
} from './constants';
import './index.scss';

const defaultDateRange =
	getSetting( 'wcAdminSettings' )?.woocommerce_default_date_range;

const SETUP_ADS_URL = getSetupAdsUrl();
const CREATE_CAMPAIGN_URL = getCreateCampaignUrl();

/**
 * Copy shown for each metrics case, per Google Ads readiness state.
 */
const PROMO_COPY = {
	revenue: {
		notReady: {
			title: __(
				'Sales a bit slow? Reach more shoppers with Google.',
				'google-listings-and-ads'
			),
			description: __(
				'Sync your catalog with Google and grow back your sales by reaching new shoppers right when they are searching to buy.',
				'google-listings-and-ads'
			),
		},
		ready: {
			title: __(
				'Sales a bit slow? Give your products a boost with Google.',
				'google-listings-and-ads'
			),
			description: __(
				'Launch a Google Ads campaign and grow back your sales by reaching shoppers who are ready to buy.',
				'google-listings-and-ads'
			),
		},
	},
	products: {
		notReady: {
			title: __(
				'Selling fewer items than usual? Reach more shoppers with Google.',
				'google-listings-and-ads'
			),
			description: __(
				'Sync your catalog with Google and sell more of your products by reaching new shoppers right when they are searching to buy.',
				'google-listings-and-ads'
			),
		},
		ready: {
			title: __(
				'Selling fewer items than usual? Give your products a boost with Google.',
				'google-listings-and-ads'
			),
			description: __(
				'Launch a Google Ads campaign and sell more of your products by reaching shoppers who are ready to buy.',
				'google-listings-and-ads'
			),
		},
	},
};

/**
 * Get the promo copy for a given metrics case and Google Ads readiness state.
 *
 * @param {string}  matchedCase     'revenue' or 'products'.
 * @param {boolean} isGoogleAdsReady Whether the merchant's Google Ads account is connected, claimed, and granted access.
 * @return {Object|null} `{ title, description, ctaLabel, ctaHref }`, or null when `matchedCase` isn't recognized.
 */
export const getPromoCopy = ( matchedCase, isGoogleAdsReady ) => {
	const caseCopy = PROMO_COPY[ matchedCase ];

	if ( ! caseCopy ) {
		return null;
	}

	const { title, description } = isGoogleAdsReady
		? caseCopy.ready
		: caseCopy.notReady;

	return {
		title,
		description,
		ctaLabel: isGoogleAdsReady
			? __( 'Launch a campaign', 'google-listings-and-ads' )
			: __( 'Get started', 'google-listings-and-ads' ),
		ctaHref: isGoogleAdsReady ? CREATE_CAMPAIGN_URL : SETUP_ADS_URL,
	};
};

/**
 * Promo Card shown on the Analytics → Overview dashboard when a merchant's store metrics
 * are trending down, mounted by the `woocommerce_dashboard_default_sections` filter
 * registered in `~/filters/analytics-overview-section`.
 *
 * @param {Object} props Props core passes down (path, query, title, controls, etc.).
 * @param {Object} [props.query] The URL query params carrying the selected range.
 * @return {JSX.Element|null} Analytics overview promo component, or `null` while resolving or once dismissed.
 */
const AnalyticsOverviewPromo = ( { query = {} } ) => {
	const { isGoogleAdsReady } = useGoogleAdsAccountReady();
	const { hasAdSpend, hasFinishedResolution: hasResolvedAdSpend } =
		useHasRecentAdSpend();
	const { set } = useDispatch( preferencesStore );
	const isDismissed = usePreference( ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY );
	const {
		hasFinishedResolution: hasResolvedMetrics,
		isDown,
		metricsCase,
	} = useProductRevenueMetricsDown( query, defaultDateRange );

	if (
		isDismissed ||
		isGoogleAdsReady === null ||
		! hasResolvedAdSpend ||
		! hasResolvedMetrics ||
		! isDown ||
		hasAdSpend
	) {
		return null;
	}

	const { title, description, ctaLabel, ctaHref } = getPromoCopy(
		metricsCase,
		isGoogleAdsReady
	);

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
		<Card className="gla-analytics-overview-promo">
			<CardBody size="large">
				<Flex
					align="flex-start"
					gap={ 8 }
					justify="flex-start"
					direction={ [ 'column', 'row' ] }
				>
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
						<Flex direction="column" align="flex-start" gap={ 4 }>
							<FlexBlock>
								<Flex
									direction="column"
									align="flex-start"
									gap={ 1 }
								>
									<h3 className="gla-analytics-overview-promo__title">
										{ title }
									</h3>
									<p className="gla-analytics-overview-promo__description">
										{ description }
									</p>
								</Flex>
							</FlexBlock>
							<FlexBlock>
								<Flex gap={ 2 } wrap>
									<FlexItem>
										<AppButton
											variant="primary"
											href={ ctaHref }
											eventName="gla_analytics_overview_promo_cta_click"
											eventProps={ {
												context:
													ANALYTICS_OVERVIEW_PROMO_CONTEXT,
												case: metricsCase,
												href: ctaHref,
											} }
										>
											{ ctaLabel }
										</AppButton>
									</FlexItem>
									<FlexItem>
										<AppButton
											variant="secondary"
											onClick={ handleDismiss }
											eventName="gla_analytics_overview_promo_dismiss_click"
											eventProps={ {
												context:
													ANALYTICS_OVERVIEW_PROMO_CONTEXT,
												case: metricsCase,
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

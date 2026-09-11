/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody, Flex, FlexItem } from '@wordpress/components';
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
import promoImage from '~/images/analytics-promo.png';
import { ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY } from './constants';
import PromoText from './promo-text';
import PromoActions from './promo-actions';
import './index.scss';

const defaultDateRange =
	getSetting( 'wcAdminSettings' )?.woocommerce_default_date_range;

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
		! metricsCase ||
		hasAdSpend
	) {
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

	return (
		<Card className="gla-analytics-overview-promo">
			<CardBody size="large">
				<Flex
					align="flex-start"
					gap={ 8 }
					justify="flex-start"
					direction={ [ 'column', 'row' ] }
				>
					<FlexItem className="gla-analytics-overview-promo__image-wrapper">
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
					<FlexItem className="gla-analytics-overview-promo__content">
						<PromoText
							metricsCase={ metricsCase }
							isGoogleAdsReady={ isGoogleAdsReady }
						/>
						<PromoActions
							isGoogleAdsReady={ isGoogleAdsReady }
							onDismiss={ handleDismiss }
						/>
					</FlexItem>
				</Flex>
			</CardBody>
		</Card>
	);
};

export default AnalyticsOverviewPromo;

/**
 * Internal dependencies
 */
import Banner from './banner';
import usePreference from '~/hooks/usePreference';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { BANNER_DISMISSED_KEY } from './constants';
import './index.scss';

/**
 * ExperienceRatingBanner component.
 *
 * Displays a banner asking users to rate their experience with Google for WooCommerce.
 *
 * @param {Object} [props]
 * @param {boolean} [props.pollProductStatistics=true] Whether to keep re-fetching product statistics while they are being calculated. Pass `false` on pages that must not poll.
 * @return {JSX.Element|null} The ExperienceRatingBanner component, or null if dismissed or Ads account is disconnected or the MC account is not ready.
 */
const ExperienceRatingBanner = ( { pollProductStatistics = true } = {} ) => {
	const { isGoogleAdsReady } = useGoogleAdsAccountReady();
	const { isReady: isMCAccountReady } = useGoogleMCAccount();
	const isDismissed = usePreference( BANNER_DISMISSED_KEY );

	if ( ! isGoogleAdsReady || ! isMCAccountReady || isDismissed ) {
		return null;
	}

	return <Banner pollProductStatistics={ pollProductStatistics } />;
};

export default ExperienceRatingBanner;

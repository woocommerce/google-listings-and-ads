/**
 * Internal dependencies
 */
import Banner from './banner';
import usePreference from '~/hooks/usePreference';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { BANNER_DISMISSED_KEY } from './constants';
import './index.scss';

/**
 * ExperienceRatingBanner component.
 *
 * Displays a banner asking users to rate their experience with Google for WooCommerce.
 *
 * @return {JSX.Element|null} The ExperienceRatingBanner component, or null if dismissed or Ads account is disconnected or the MC account is not ready.
 */
const ExperienceRatingBanner = () => {
	const { hasGoogleAdsConnection } = useGoogleAdsAccount();
	const { isReady: isMCAccountReady } = useGoogleMCAccount();
	const isDismissed = usePreference( BANNER_DISMISSED_KEY );

	if ( ! hasGoogleAdsConnection || ! isMCAccountReady || isDismissed ) {
		return null;
	}

	return <Banner />;
};

export default ExperienceRatingBanner;

/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE, DAY_IN_SECONDS } from '~/constants';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import usePreference from '~/hooks/usePreference';
import Banner from './banner';
import './index.scss';

const PREFERENCE_BANNER_KEY = 'pmax-improve-assets-banner';
const ACTION_TYPE_DISMISS = 'dismiss';

/**
 * Displays a dismissible banner prompting users to improve assets for their highest-spending enabled Performance Max (PMAX) campaign.
 *
 * The banner is shown only if:
 * - The preference actionTime is not set or has expired.
 * - There are relevant asset improvement recommendations.
 * - The user has a connected Google Ads account.
 *
 * When dismissed, the banner will not reappear until the expiry time elapses.
 * Clicking "Improve Assets" navigates to the asset group edit page for the highest-spending PMAX campaign.
 *
 * @return {JSX.Element|null} The banner component, or null if not applicable.
 */
const PMaxImproveAssetsBanner = () => {
	const { isGoogleAdsReady } = useGoogleAdsAccountReady();
	const { set } = useDispatch( preferencesStore );
	const { actionTime, actionType } =
		usePreference( PREFERENCE_BANNER_KEY ) || {};

	const handleOnBannerDismissed = useCallback( () => {
		set( PREFERENCES_STORE_NAMESPACE, PREFERENCE_BANNER_KEY, {
			actionType: ACTION_TYPE_DISMISS,
			actionTime: Date.now(),
		} );
	}, [ set ] );

	// Don't display the banner if the banner has been dismissed less than 30 days ago
	// or there is no Google Ads connection.
	if (
		! isGoogleAdsReady ||
		( actionType === ACTION_TYPE_DISMISS &&
			Date.now() < actionTime + 30 * DAY_IN_SECONDS * 1000 )
	) {
		return null;
	}

	return <Banner onBannerDismissed={ handleOnBannerDismissed } />;
};

export default PMaxImproveAssetsBanner;

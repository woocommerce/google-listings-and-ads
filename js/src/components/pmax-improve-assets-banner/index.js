/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useEffect, useCallback } from '@wordpress/element';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE, DAY_IN_SECONDS } from '~/constants';
import usePreference from '~/hooks/usePreference';
import Banner from './banner';
import './index.scss';

const PREFERENCE_BANNER_KEY = 'pmax-improve-assets-banner';

/**
 * Displays a dismissible banner prompting users to improve assets for their highest-spending enabled Performance Max (PMAX) campaign.
 *
 * The banner is shown only if:
 * - The preference expiry is undefined or expired.
 * - There are enabled PMAX campaigns.
 * - There are relevant asset improvement recommendations.
 *
 * When dismissed, the banner will not reappear until the expiry time elapses.
 * Clicking "Improve Assets" navigates to the asset group edit page for the highest-spending PMAX campaign.
 * Another property, "hasRecommendations" is used to track if there are any recommendations available.
 *
 * @return {JSX.Element|null} The banner component, or null if not applicable.
 */
const PMaxImproveAssetsBanner = () => {
	const { set } = useDispatch( preferencesStore );
	const { actionTime, actionType } =
		usePreference( PREFERENCE_BANNER_KEY ) || {};

	// useEffect( () => {
	// 	if ( expiry !== undefined && Date.now() >= expiry ) {
	// 		set( PREFERENCES_STORE_NAMESPACE, PREFERENCE_BANNER_KEY, {
	// 			actionTime: undefined,
	// 			actionType: undefined,
	// 		} );
	// 	}
	// }, [ actionTime, actionType, set ] );

	const handleOnBannerDismissed = useCallback( () => {
		set( PREFERENCES_STORE_NAMESPACE, PREFERENCE_BANNER_KEY, {
			actionType: 'dismiss',
			actionTime: Date.now(),
		} );
	}, [ set ] );

	const handleOnBannerActioned = useCallback( () => {
		set( PREFERENCES_STORE_NAMESPACE, PREFERENCE_BANNER_KEY, {
			actionType: 'editAssets',
			actionTime: Date.now(),
		} );
	}, [ set ] );

	const handleOnBannerShown = useCallback( () => {
		// if ( hasRecommendations ) {
		// 	return;
		// }
		// set( PREFERENCES_STORE_NAMESPACE, PREFERENCE_BANNER_KEY, {
		// 	actionTime: undefined,
		// 	actionType: undefined,
		// } );
	}, [ set, actionTime, actionType ] );

	// Don't display the banner if the banner has been dismissed less than 30 days ago.
	if ( actionType === 'dismiss' && Date.now() + 30 * DAY_IN_SECONDS * 1000 > actionTime ) {
		return null;
	}


	return (
		<Banner
			onBannerDismissed={ handleOnBannerDismissed }
			onBannerShown={ handleOnBannerShown }
			onBannerActioned={ handleOnBannerActioned }
		/>
	);
};

export default PMaxImproveAssetsBanner;

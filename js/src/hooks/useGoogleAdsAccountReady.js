/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '~/hooks/useGoogleAdsAccountStatus';

/**
 * @typedef {Object} GoogleAdsAccountReady
 * @property {null|boolean} isGoogleAdsReady True if the Google Ads account is connected, claimed, and granted access. `null` if the state is not yet determined.
 * @property {null|boolean} isLinkedToMerchantCenter True if the Google Ads account is linked to a Merchant Center account. `null` if the state is not yet determined.
 */

const notDetermined = {
	isGoogleAdsReady: null,
	isLinkedToMerchantCenter: null,
};

/**
 * Hook to check if the Google Ads account is ready or connected to a Merchant Center account.
 * This is used to determine if the user can proceed to the next step.
 *
 * @return {GoogleAdsAccountReady} The state of the Google Ads account.
 */
const useGoogleAdsAccountReady = () => {
	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: adsAccountResolved,
	} = useGoogleAdsAccount();
	const {
		hasAccess,
		step,
		hasFinishedResolution: adsAccountStatusResolved,
	} = useGoogleAdsAccountStatus();

	if ( ! adsAccountResolved || ! adsAccountStatusResolved ) {
		return notDetermined;
	}

	const isLinkedToMerchantCenter =
		hasAccess && [ '', 'billing' ].includes( step );

	const isGoogleAdsReady =
		hasGoogleAdsConnection &&
		hasAccess &&
		[ '', 'billing', 'link_merchant' ].includes( step );

	return {
		isGoogleAdsReady,
		isLinkedToMerchantCenter,
	};
};

export default useGoogleAdsAccountReady;

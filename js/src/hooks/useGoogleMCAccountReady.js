/**
 * Internal dependencies
 */
import useGoogleMCAccount from './useGoogleMCAccount';
import { GOOGLE_MC_ACCOUNT_STATUS } from '.~/constants';

const useGoogleMCAccountReady = () => {
	const { hasGoogleMCConnection, googleMCAccount } = useGoogleMCAccount();

	// MC is ready when we have a connection and preconditions are met.
	// The `link_ads` step will be resolved when the Ads account is connected
	// since these can be connected in any order.
	return (
		hasGoogleMCConnection &&
		[ '', 'link_merchant' ].includes( googleMCAccount?.step )
	);
};

export default useGoogleMCAccountReady;

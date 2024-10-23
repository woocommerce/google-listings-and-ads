/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import { GOOGLE_MC_ACCOUNT_STATUS } from '.~/constants';
import useGoogleAccount from './useGoogleAccount';

/**
 * @typedef {import('.~/data/selectors').GoogleMCAccount} GoogleMCAccount
 *
 * @typedef {Object} GoogleMCAccountPayload
 * @property {GoogleMCAccount|undefined} googleMCAccount The connection data of Google Merchant Center account associated with GLA.
 * @property {boolean} isResolving Whether resolution is in progress.
 * @property {boolean} hasFinishedResolution Whether resolution has completed.
 * @property {boolean} isPreconditionReady Whether the precondition of continued connection processing is fulfilled.
 */

const googleMCAccountSelector = 'getGoogleMCAccount';

/**
 * A hook to load the connection data of Google Merchant Center account.
 *
 * @return {GoogleMCAccountPayload} The data and its state.
 */
const useGoogleMCAccount = () => {
	const {
		google,
		scope,
		isResolving: isResolvingGoogle,
		hasFinishedResolution: hasFinishedResolutionGoogle,
	} = useGoogleAccount();

	return useSelect(
		( select ) => {
			if ( ! google || google.active === 'no' || ! scope.gmcRequired ) {
				return {
					googleMCAccount: undefined,
					isResolving: isResolvingGoogle,
					hasFinishedResolution: hasFinishedResolutionGoogle,
					// If a user has not yet connected their Google account or the connected Google account
					// has not been granted necessary access permissions for Google Merchant Center, then
					// the precondition doesn't meet.
					isPreconditionReady: false,
					isConnected: false,
				};
			}

			const selector = select( STORE_KEY );
			const googleMCAccount = selector[ googleMCAccountSelector ]();
			const isResolvingGoogleMCAccount = selector.isResolving(
				googleMCAccountSelector
			);

			const hasGoogleMCConnection = [
				GOOGLE_MC_ACCOUNT_STATUS.CONNECTED,
				GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE,
			].includes( googleMCAccount?.status );

			return {
				googleMCAccount,
				isResolving: isResolvingGoogleMCAccount,
				hasFinishedResolution: selector.hasFinishedResolution(
					googleMCAccountSelector
				),
				isPreconditionReady: true,
				hasGoogleMCConnection,
				// MC is ready when we have a connection and preconditions are met.
				// The `link_ads` step will be resolved when the Ads account is connected
				// since these can be connected in any order.
				isConnected:
					googleMCAccount?.status ===
						GOOGLE_MC_ACCOUNT_STATUS.CONNECTED ||
					( googleMCAccount?.status ===
						GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE &&
						googleMCAccount?.step === 'link_ads' ),
			};
		},
		[
			google,
			scope.gmcRequired,
			isResolvingGoogle,
			hasFinishedResolutionGoogle,
		]
	);
};

export default useGoogleMCAccount;

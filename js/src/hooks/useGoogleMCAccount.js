/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useGoogleAccount from './useGoogleAccount';
import { GOOGLE_MC_ACCOUNT_STATUS } from '.~/constants';

/**
 * @typedef {import('.~/data/selectors').GoogleMCAccount} GoogleMCAccount
 *
 * @typedef {Object} GoogleMCAccountPayload
 * @property {GoogleMCAccount|undefined} googleMCAccount The connection data of Google Merchant Center account associated with GLA.
 * @property {boolean} isResolving Whether resolution is in progress.
 * @property {boolean} hasFinishedResolution Whether resolution has completed.
 * @property {boolean} isPreconditionReady Whether the precondition of continued connection processing is fulfilled.
 * @property {boolean} hasGoogleMCConnection Whether the user has a Google Merchant Center account connection established.
 * @property {boolean} isReady Whether the user has a Google Merchant Center account is in connected state.
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
					hasGoogleMCConnection: false,
					isReady: false,
				};
			}

			const selector = select( STORE_KEY );
			const acc = selector[ googleMCAccountSelector ]();
			const isResolvingGoogleMCAccount = selector.isResolving(
				googleMCAccountSelector
			);

			const hasGoogleMCConnection = [
				GOOGLE_MC_ACCOUNT_STATUS.CONNECTED,
				GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE,
			].includes( acc?.status );

			const isReady =
				hasGoogleMCConnection &&
				( acc.status === GOOGLE_MC_ACCOUNT_STATUS.CONNECTED ||
					( acc.status === GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE &&
						acc?.step === 'link_ads' ) );

			return {
				googleMCAccount: acc,
				isResolving: isResolvingGoogleMCAccount,
				hasFinishedResolution: selector.hasFinishedResolution(
					googleMCAccountSelector
				),
				isPreconditionReady: true,
				hasGoogleMCConnection,
				isReady,
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

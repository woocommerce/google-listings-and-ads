/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
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
				};
			}

			const {
				getGoogleMCAccount,
				isResolving,
				hasFinishedResolution,
			} = select( STORE_KEY );

			return {
				googleMCAccount: getGoogleMCAccount(),
				isResolving: isResolving( 'getGoogleMCAccount' ),
				hasFinishedResolution: hasFinishedResolution(
					'getGoogleMCAccount'
				),
				isPreconditionReady: true,
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

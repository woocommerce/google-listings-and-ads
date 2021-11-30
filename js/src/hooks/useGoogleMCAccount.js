/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useGoogleAccount from './useGoogleAccount';

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

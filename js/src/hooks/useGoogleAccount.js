/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import { STORE_KEY } from '.~/data/constants';
import toScopeState from '.~/utils/toScopeState';
import useJetpackAccount from './useJetpackAccount';

const useGoogleAccount = () => {
	const {
		jetpack,
		isResolving: isResolvingJetpack,
		hasFinishedResolution: hasFinishedResolutionJetpack,
	} = useJetpackAccount();

	return useSelect(
		( select ) => {
			if ( ! jetpack || jetpack.active === 'no' ) {
				return {
					google: undefined,
					scope: toScopeState( glaData.adsSetupComplete ),
					isResolving: isResolvingJetpack,
					hasFinishedResolution: hasFinishedResolutionJetpack,
				};
			}

			const {
				getGoogleAccount,
				isResolving,
				hasFinishedResolution,
			} = select( STORE_KEY );
			const google = getGoogleAccount();

			return {
				google,
				scope: toScopeState( glaData.adsSetupComplete, google?.scope ),
				isResolving: isResolving( 'getGoogleAccount' ),
				hasFinishedResolution: hasFinishedResolution(
					'getGoogleAccount'
				),
			};
		},
		[ jetpack, isResolvingJetpack, hasFinishedResolutionJetpack ]
	);
};

export default useGoogleAccount;

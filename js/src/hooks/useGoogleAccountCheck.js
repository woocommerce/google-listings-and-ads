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

const useGoogleAccountCheck = () => {
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
				getExistingGoogleAdsAccounts,
				isResolving,
				hasFinishedResolution,
			} = select( STORE_KEY );
			const google = getGoogleAccount();

			if ( google?.active === 'no' ) {
				return {
					google,
					existingAccounts: null,
					isResolving: isResolving( 'getGoogleAccount' ),
					hasFinishedResolution:
						hasFinishedResolution( 'getGoogleAccount' ),
				};
			}

			const existingAccounts = getExistingGoogleAdsAccounts();
			return {
				google,
				existingAccounts,
				isResolving: isResolving( 'getExistingGoogleAdsAccounts' ),
				hasFinishedResolution: hasFinishedResolution(
					'getExistingGoogleAdsAccounts'
				),
			};
		},
		[ jetpack, isResolvingJetpack, hasFinishedResolutionJetpack ]
	);
};

export default useGoogleAccountCheck;

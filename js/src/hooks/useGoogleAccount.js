/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useJetpackAccount from './useJetpackAccount';

const SCOPE = {
	// Manage product listings and accounts for Google Shopping
	CONTENT: 'https://www.googleapis.com/auth/content',
	// Manage new site verifications with Google
	SITE_VERIFICATION:
		'https://www.googleapis.com/auth/siteverification.verify_only',
	// Manage AdWords campaigns
	AD_WORDS: 'https://www.googleapis.com/auth/adwords',
};

function toScopeState( scopes = [] ) {
	const state = {
		adsRequired: scopes.includes( SCOPE.AD_WORDS ),
	};

	state.gmcRequired =
		scopes.includes( SCOPE.CONTENT ) &&
		scopes.includes( SCOPE.SITE_VERIFICATION );

	state.allRequired = state.gmcRequired && state.adsRequired;
	return state;
}

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
					scope: toScopeState(),
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
				scope: toScopeState( google?.scope ),
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

/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { STORE_KEY } from '~/data/constants';
import toScopeState from '~/utils/toScopeState';
import useJetpackAccount from './useJetpackAccount';

const useGoogleAccount = () => {
	const {
		jetpack,
		isResolving: isResolvingJetpack,
		hasFinishedResolution: hasFinishedResolutionJetpack,
	} = useJetpackAccount();

	const { google, isResolving, hasFinishedResolution } = useSelect(
		( select ) => {
			if ( ! jetpack || jetpack.active === 'no' ) {
				return {
					google: undefined,
					isResolving: isResolvingJetpack,
					hasFinishedResolution: hasFinishedResolutionJetpack,
				};
			}

			const selector = select( STORE_KEY );

			return {
				google: selector.getGoogleAccount(),
				isResolving: selector.isResolving( 'getGoogleAccount' ),
				hasFinishedResolution:
					selector.hasFinishedResolution( 'getGoogleAccount' ),
			};
		},
		[ jetpack, isResolvingJetpack, hasFinishedResolutionJetpack ]
	);

	// Derived outside `useSelect`, as `toScopeState` returns a new object on every call.
	const scope = useMemo(
		() => toScopeState( glaData.adsSetupComplete, google?.scope ),
		[ google?.scope ]
	);

	return { google, scope, isResolving, hasFinishedResolution };
};

export default useGoogleAccount;

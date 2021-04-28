/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
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
					isResolving: isResolvingJetpack,
					hasFinishedResolution: hasFinishedResolutionJetpack,
				};
			}

			const {
				getGoogleAccount,
				isResolving,
				hasFinishedResolution,
			} = select( STORE_KEY );

			return {
				google: getGoogleAccount(),
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

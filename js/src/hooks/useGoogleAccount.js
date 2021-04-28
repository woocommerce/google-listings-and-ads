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

			const google = select( STORE_KEY ).getGoogleAccount();
			const isResolving = select( STORE_KEY ).isResolving(
				'getGoogleAccount'
			);
			const hasFinishedResolution = select(
				STORE_KEY
			).hasFinishedResolution( 'getGoogleAccount' );

			const result = {
				google,
				isResolving,
				hasFinishedResolution,
			};

			return result;
		},
		[ jetpack, isResolvingJetpack, hasFinishedResolutionJetpack ]
	);
};

export default useGoogleAccount;

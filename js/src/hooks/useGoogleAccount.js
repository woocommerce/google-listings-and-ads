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
	const { jetpack, isResolving, hasFinishedResolution } = useJetpackAccount();

	return useSelect( ( select ) => {
		if ( ! jetpack || jetpack.active === 'no' ) {
			return {
				google: undefined,
				isResolving,
				hasFinishedResolution,
			};
		}

		const acc = select( STORE_KEY ).getGoogleAccount();
		const isResolvingGoogle = select( STORE_KEY ).isResolving(
			'getGoogleAccount'
		);
		const hasFinishedResolutionGoogle = select(
			STORE_KEY
		).hasFinishedResolution( 'getGoogleAccount' );

		return {
			google: acc,
			isResolving: isResolvingGoogle,
			hasFinishedResolution: hasFinishedResolutionGoogle,
		};
	} );
};

export default useGoogleAccount;

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
		const {
			getGoogleAccount,
			isResolving: isResolvingFn,
			hasFinishedResolution: hasFinishedResolutionFn,
		} = select( STORE_KEY );

		if ( ! jetpack || jetpack.active === 'no' ) {
			return {
				google: undefined,
				isResolving,
				hasFinishedResolution,
			};
		}

		const acc = getGoogleAccount();
		const isResolvingGoogle = isResolvingFn( 'getGoogleAccount' );
		const hasFinishedResolutionGoogle = hasFinishedResolutionFn(
			'getGoogleAccount'
		);

		const result = {
			google: acc,
			isResolving: isResolvingGoogle,
			hasFinishedResolution: hasFinishedResolutionGoogle,
		};

		return result;
	} );
};

export default useGoogleAccount;

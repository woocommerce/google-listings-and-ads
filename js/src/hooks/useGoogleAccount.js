/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
// import useJetpackAccount from './useJetpackAccount';

const useGoogleAccount = () => {
	// const { jetpack, isResolving, hasFinishedResolution } = useJetpackAccount();

	return useSelect( ( select ) => {
		const jetpack = select( STORE_KEY ).getJetpackAccount();
		const isResolving = select( STORE_KEY ).isResolving(
			'getJetpackAccount'
		);
		const hasFinishedResolution = select( STORE_KEY ).hasFinishedResolution(
			'getJetpackAccount'
		);

		if ( ! jetpack || jetpack.active === 'no' ) {
			return {
				google: undefined,
				isResolving,
				hasFinishedResolution,
			};
		}

		const {
			getGoogleAccount,
			isResolving: isResolvingFn,
			hasFinishedResolution: hasFinishedResolutionFn,
		} = select( STORE_KEY );

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

		console.log( 'result google: ', result );

		return result;
	} );
};

export default useGoogleAccount;

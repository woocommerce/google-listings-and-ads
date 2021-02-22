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
	const { jetpack, isResolving } = useJetpackAccount();

	return useSelect( ( select ) => {
		if ( ! jetpack || jetpack.active === 'no' ) {
			return {
				google: undefined,
				isResolving,
			};
		}

		const acc = select( STORE_KEY ).getGoogleAccount();
		const isResolvingGoogle = select( STORE_KEY ).isResolving(
			'getGoogleAccount'
		);

		return { google: acc, isResolving: isResolvingGoogle };
	} );
};

export default useGoogleAccount;

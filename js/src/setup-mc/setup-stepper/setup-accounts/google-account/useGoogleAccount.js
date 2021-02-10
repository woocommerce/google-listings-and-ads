/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useGoogleAccount = () => {
	return useSelect( ( select ) => {
		const acc = select( STORE_KEY ).getGoogleAccount();
		const resolving = select( STORE_KEY ).isResolving( 'getGoogleAccount' );

		return { google: acc, isResolving: resolving };
	} );
};

export default useGoogleAccount;

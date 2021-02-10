/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useGoogleMCAccount = () => {
	return useSelect( ( select ) => {
		const acc = select( STORE_KEY ).getGoogleMCAccount();
		const resolving = select( STORE_KEY ).isResolving(
			'getGoogleMCAccount'
		);

		return { googleMCAccount: acc, isResolving: resolving };
	} );
};

export default useGoogleMCAccount;

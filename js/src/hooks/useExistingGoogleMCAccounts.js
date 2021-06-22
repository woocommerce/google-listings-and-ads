/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useExistingGoogleMCAccounts = () => {
	return useSelect( ( select ) => {
		const existingAccounts = select(
			STORE_KEY
		).getExistingGoogleMCAccounts();
		const isResolving = select( STORE_KEY ).isResolving(
			'getExistingGoogleMCAccounts'
		);

		return { existingAccounts, isResolving };
	}, [] );
};

export default useExistingGoogleMCAccounts;

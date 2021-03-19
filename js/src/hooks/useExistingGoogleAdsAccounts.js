/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useExistingGoogleAdsAccounts = () => {
	return useSelect( ( select ) => {
		const existingAccounts = select(
			STORE_KEY
		).getExistingGoogleAdsAccounts();
		const isResolving = select( STORE_KEY ).isResolving(
			'getExistingGoogleAdsAccounts'
		);

		return { existingAccounts, isResolving };
	} );
};

export default useExistingGoogleAdsAccounts;

/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useGoogleAccount from './useGoogleAccount';

const useExistingGoogleAdsAccounts = () => {
	const { google, isResolving: isResolvingGoogleAccount } =
		useGoogleAccount();

	return useSelect(
		( select ) => {
			if ( ! google || google.active === 'no' ) {
				return {
					existingAccounts: [],
					isResolving: isResolvingGoogleAccount,
				};
			}

			const existingAccounts =
				select( STORE_KEY ).getExistingGoogleAdsAccounts();
			const isResolving = select( STORE_KEY ).isResolving(
				'getExistingGoogleAdsAccounts'
			);

			return { existingAccounts, isResolving };
		},
		[ google, isResolvingGoogleAccount ]
	);
};

export default useExistingGoogleAdsAccounts;

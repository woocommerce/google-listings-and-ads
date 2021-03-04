/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useGoogleAccount from './useGoogleAccount';

const useGoogleAdsAccount = () => {
	const { google, isResolving } = useGoogleAccount();

	return useSelect( ( select ) => {
		if ( ! google || google.active === 'no' ) {
			return {
				googleAdsAccount: undefined,
				isResolving,
			};
		}

		const acc = select( STORE_KEY ).getGoogleAdsAccount();
		const isResolvingGoogleAdsAccount = select( STORE_KEY ).isResolving(
			'getGoogleAdsAccount'
		);

		return {
			googleAdsAccount: acc,
			isResolving: isResolvingGoogleAdsAccount,
		};
	} );
};

export default useGoogleAdsAccount;

/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useGoogleAccount from './useGoogleAccount';

const useGoogleMCAccount = () => {
	const { google, isResolving } = useGoogleAccount();

	return useSelect( ( select ) => {
		if ( ! google || google.active === 'no' ) {
			return {
				googleMCAccount: undefined,
				isResolving,
			};
		}

		const acc = select( STORE_KEY ).getGoogleMCAccount();
		const isResolvingGoogleMCAccount = select( STORE_KEY ).isResolving(
			'getGoogleMCAccount'
		);

		return {
			googleMCAccount: acc,
			isResolving: isResolvingGoogleMCAccount,
		};
	} );
};

export default useGoogleMCAccount;

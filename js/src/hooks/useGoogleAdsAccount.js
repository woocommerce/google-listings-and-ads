/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import { useAppDispatch } from '.~/data';
import useGoogleAccount from './useGoogleAccount';

const useGoogleAdsAccount = () => {
	const { google, isResolving } = useGoogleAccount();

	const dispatcher = useAppDispatch();
	const refetchGoogleAdsAccount = useCallback( () => {
		dispatcher.invalidateResolution( 'getGoogleAdsAccount', [] );
	}, [ dispatcher ] );

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
			refetchGoogleAdsAccount,
		};
	} );
};

export default useGoogleAdsAccount;

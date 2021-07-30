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

const googleAdsAccountSelector = 'getGoogleAdsAccount';

const useGoogleAdsAccount = () => {
	const { google, isResolving } = useGoogleAccount();

	const dispatcher = useAppDispatch();
	const refetchGoogleAdsAccount = useCallback( () => {
		dispatcher.invalidateResolution( googleAdsAccountSelector, [] );
	}, [ dispatcher ] );

	return useSelect(
		( select ) => {
			if ( ! google || google.active === 'no' ) {
				return {
					googleAdsAccount: undefined,
					isResolving,
				};
			}

			const selector = select( STORE_KEY );
			const acc = selector[ googleAdsAccountSelector ]();
			const isResolvingGoogleAdsAccount = selector.isResolving(
				googleAdsAccountSelector
			);

			return {
				googleAdsAccount: acc,
				isResolving: isResolvingGoogleAdsAccount,
				refetchGoogleAdsAccount,
				hasFinishedResolution: selector.hasFinishedResolution(
					googleAdsAccountSelector
				),
			};
		},
		[ google, isResolving, refetchGoogleAdsAccount ]
	);
};

export default useGoogleAdsAccount;

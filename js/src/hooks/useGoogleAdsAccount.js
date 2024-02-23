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
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';
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

			// The "incomplete" status means there is a connected account but billing is not yet set
			// so it's considered as `true`.
			// The main reason for not using a naming like `isGoogleAdsConnected` here is to make
			// a slight distinction from the "connected" status.
			const hasGoogleAdsConnection = [
				GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED,
				GOOGLE_ADS_ACCOUNT_STATUS.INCOMPLETE,
			].includes( acc?.status );

			const {
				getAdsAccountStatus,
				isResolving: isResolvingGoogleAdsAccountStatus,
				hasFinishedResolution:
					hasFinishedResolutionGoogleAdsAccountStatus,
			} = select( STORE_KEY );
			const googleAdsAccountStatus = getAdsAccountStatus();

			return {
				googleAdsAccount: acc,
				isResolving: isResolvingGoogleAdsAccount,
				refetchGoogleAdsAccount,
				hasFinishedResolution: selector.hasFinishedResolution(
					googleAdsAccountSelector
				),
				hasGoogleAdsConnection,
				isResolvingGoogleAdsAccountStatus,
				googleAdsAccountStatus,
				hasFinishedResolutionGoogleAdsAccountStatus,
			};
		},
		[ google, isResolving, refetchGoogleAdsAccount ]
	);
};

export default useGoogleAdsAccount;

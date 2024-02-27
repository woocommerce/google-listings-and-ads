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

const selectorName = 'getAdsAccountStatus';

const useGoogleAdsAccountStatus = () => {
	const dispatcher = useAppDispatch();
	const refetchGoogleAdsAccountStatus = useCallback( () => {
		dispatcher.invalidateResolution( selectorName, [] );
	}, [ dispatcher ] );

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const { hasAccess, inviteLink } = selector[ selectorName ]();

			return {
				hasAccess,
				inviteLink,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
				refetchGoogleAdsAccountStatus,
			};
		},
		[ refetchGoogleAdsAccountStatus ]
	);
};

export default useGoogleAdsAccountStatus;

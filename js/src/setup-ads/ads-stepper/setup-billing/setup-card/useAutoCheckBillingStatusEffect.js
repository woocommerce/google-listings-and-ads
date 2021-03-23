/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';

const useAutoCheckBillingStatusEffect = ( onStatusApproved = () => {} ) => {
	const { receiveGoogleAdsAccountBillingStatus } = useAppDispatch();

	const checkStatus = useCallback( async () => {
		const billingStatus = await apiFetch( {
			path: '/wc/gla/ads/billing-status',
		} );

		if ( billingStatus.status === 'approved' ) {
			await onStatusApproved();
			receiveGoogleAdsAccountBillingStatus( billingStatus );
		}
	}, [ onStatusApproved, receiveGoogleAdsAccountBillingStatus ] );

	useWindowFocusCallbackIntervalEffect( checkStatus, 30 );
};

export default useAutoCheckBillingStatusEffect;

/**
 * External dependencies
 */
import { useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useWindowFocusPollingEffect from './useWindowFocusPollingEffect';

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

	// check billing status when window got focus.
	useEffect( () => {
		const handleWindowFocus = () => {
			checkStatus();
		};

		window.addEventListener( 'focus', handleWindowFocus );

		return () => {
			window.removeEventListener( 'focus', handleWindowFocus );
		};
	}, [ checkStatus ] );

	useWindowFocusPollingEffect( checkStatus, 30 );
};

export default useAutoCheckBillingStatusEffect;

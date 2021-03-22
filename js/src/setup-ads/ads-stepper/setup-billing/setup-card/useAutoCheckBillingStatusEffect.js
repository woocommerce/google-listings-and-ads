/**
 * External dependencies
 */
import { useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useWindowFocusRef from '.~/hooks/useWindowFocusRef';

const pollIntervalInSeconds = 30;

const useAutoCheckBillingStatusEffect = ( onStatusApproved = () => {} ) => {
	const focusRef = useWindowFocusRef();
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

	// poll for billing status only when window is in focus.
	useEffect( () => {
		const intervalID = setInterval( () => {
			if ( focusRef.current ) {
				checkStatus();
			}
		}, pollIntervalInSeconds * 1000 );

		return () => clearInterval( intervalID );
	}, [ checkStatus, focusRef ] );
};

export default useAutoCheckBillingStatusEffect;

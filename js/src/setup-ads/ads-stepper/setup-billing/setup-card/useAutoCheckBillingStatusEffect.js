/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';

const retryIntervalInSeconds = 10;

const useAutoCheckBillingStatusEffect = ( onStatusApproved = () => {} ) => {
	// used to keep track whether current window is in focus.
	const focusRef = useRef( true );

	const { fetchGoogleAdsAccountBillingStatus } = useAppDispatch();
	const { billingStatus = {} } = useGoogleAdsAccountBillingStatus();
	const { status } = billingStatus;

	// check billing status when window got focus
	// and set the focusRef accordingly.
	useEffect( () => {
		const handleWindowFocus = () => {
			focusRef.current = true;
			fetchGoogleAdsAccountBillingStatus();
		};

		const handleWindowBlur = () => {
			focusRef.current = false;
		};

		window.addEventListener( 'focus', handleWindowFocus );
		window.addEventListener( 'blur', handleWindowBlur );

		return () => {
			window.removeEventListener( 'focus', handleWindowFocus );
			window.removeEventListener( 'blur', handleWindowBlur );
		};
	}, [ fetchGoogleAdsAccountBillingStatus ] );

	// poll for billing status only when window is in focus.
	useEffect( () => {
		const intervalID = setInterval( () => {
			if ( focusRef.current ) {
				fetchGoogleAdsAccountBillingStatus();
			}
		}, retryIntervalInSeconds * 1000 );

		return () => clearInterval( intervalID );
	}, [ fetchGoogleAdsAccountBillingStatus ] );

	useEffect( () => {
		if ( status === 'approved' ) {
			onStatusApproved();
		}
	}, [ status, onStatusApproved ] );
};

export default useAutoCheckBillingStatusEffect;

/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import useWindowFocusRef from '.~/hooks/useWindowFocusRef';

const retryIntervalInSeconds = 10;

const useAutoCheckBillingStatusEffect = ( onStatusApproved = () => {} ) => {
	const focusRef = useWindowFocusRef();
	const { fetchGoogleAdsAccountBillingStatus } = useAppDispatch();
	const { billingStatus = {} } = useGoogleAdsAccountBillingStatus();
	const { status } = billingStatus;

	// check billing status when window got focus.
	useEffect( () => {
		const handleWindowFocus = () => {
			fetchGoogleAdsAccountBillingStatus();
		};

		window.addEventListener( 'focus', handleWindowFocus );

		return () => {
			window.removeEventListener( 'focus', handleWindowFocus );
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
	}, [ fetchGoogleAdsAccountBillingStatus, focusRef ] );

	useEffect( () => {
		if ( status === 'approved' ) {
			onStatusApproved();
		}
	}, [ status, onStatusApproved ] );
};

export default useAutoCheckBillingStatusEffect;

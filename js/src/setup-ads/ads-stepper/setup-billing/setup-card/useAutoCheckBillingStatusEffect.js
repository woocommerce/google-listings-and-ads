/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';

const retryIntervalInSeconds = 10;

const useAutoCheckBillingStatusEffect = ( onStatusApproved = () => {} ) => {
	const { fetchGoogleAdsAccountBillingStatus } = useAppDispatch();
	const { billingStatus = {} } = useGoogleAdsAccountBillingStatus();
	const { status } = billingStatus;

	useEffect( () => {
		const intervalID = setInterval(
			fetchGoogleAdsAccountBillingStatus,
			retryIntervalInSeconds * 1000
		);

		return () => clearInterval( intervalID );
	}, [ fetchGoogleAdsAccountBillingStatus ] );

	useEffect( () => {
		if ( status === 'approved' ) {
			onStatusApproved();
		}
	}, [ status, onStatusApproved ] );
};

export default useAutoCheckBillingStatusEffect;

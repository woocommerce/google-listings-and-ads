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

const useAutoCheckBillingStatusEffect = ( onSetupComplete = () => {} ) => {
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
			onSetupComplete();
		}
	}, [ status, onSetupComplete ] );
};

export default useAutoCheckBillingStatusEffect;

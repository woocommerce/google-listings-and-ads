/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';

const retryIntervalInSeconds = 10;

const useAutoCheckBillingStatusAndRedirectEffect = () => {
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
			getHistory().push(
				getNewPath(
					{ guide: 'campaign-creation-success' },
					'/google/dashboard'
				)
			);
		}
	}, [ status ] );
};

export default useAutoCheckBillingStatusAndRedirectEffect;

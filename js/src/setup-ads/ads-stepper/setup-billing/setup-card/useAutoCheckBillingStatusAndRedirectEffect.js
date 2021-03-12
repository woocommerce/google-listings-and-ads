/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';

const retryIntervalInSeconds = 10;

const useAutoCheckBillingStatusAndRedirectEffect = async () => {
	const { receiveGoogleAdsAccountBillingStatus } = useAppDispatch();

	const checkStatusAndRedirect = useCallback( async () => {
		const billingStatus = await apiFetch( {
			path: '/wc/gla/ads/billing-status',
		} );

		if ( billingStatus.status === 'approved' ) {
			receiveGoogleAdsAccountBillingStatus( billingStatus );
			getHistory().push( getNewPath( {}, '/google/dashboard' ) );
		}
	}, [ receiveGoogleAdsAccountBillingStatus ] );

	useEffect( () => {
		const intervalID = setInterval( () => {
			checkStatusAndRedirect();
		}, retryIntervalInSeconds * 1000 );

		return () => clearInterval( intervalID );
	}, [ checkStatusAndRedirect ] );
};

export default useAutoCheckBillingStatusAndRedirectEffect;

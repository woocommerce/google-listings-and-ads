/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';

/**
 * Make API call to complete Google Ads account setup.
 */
const completeGoogleAdsAccountSetup = () => {
	return apiFetch( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
	} );
};

const useAutoCheckBillingStatusEffect = ( onStatusApproved = noop ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { receiveGoogleAdsAccountBillingStatus } = useAppDispatch();
	const prevStatusRef = useRef();

	const onStatusApprovedRef = useRef();
	onStatusApprovedRef.current = onStatusApproved;

	const checkStatusAndCompleteSetup = useCallback( async () => {
		const prevStatus = prevStatusRef.current;
		const billingStatus = await apiFetch( {
			path: '/wc/gla/ads/billing-status',
		} );

		prevStatusRef.current = billingStatus.status;

		// Prevent the completion API and callback from being called unwanted times due to
		// the regain focus or interval timer after the status is approved.
		if (
			prevStatus === billingStatus.status ||
			billingStatus.status !== GOOGLE_ADS_BILLING_STATUS.APPROVED
		) {
			return;
		}

		try {
			await completeGoogleAdsAccountSetup();
			await onStatusApprovedRef.current();
			receiveGoogleAdsAccountBillingStatus( billingStatus );
		} catch ( e ) {
			createNotice(
				'error',
				__(
					'Unable to complete your Google Ads account setup. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	}, [ createNotice, receiveGoogleAdsAccountBillingStatus ] );

	useWindowFocusCallbackIntervalEffect( checkStatusAndCompleteSetup, 30 );
};

export default useAutoCheckBillingStatusEffect;

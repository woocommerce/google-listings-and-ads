/**
 * External dependencies
 */
import noop from 'lodash/noop';
import { __ } from '@wordpress/i18n';
import { useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import { API_NAMESPACE } from '.~/data/constants';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';

const useAutoCheckAdsAccountStatus = ( onAccessGranted = noop ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { receiveAdsAccountStatus } = useAppDispatch();
	const hasAccessRef = useRef();

	const onAccessGrantedRef = useRef();
	onAccessGrantedRef.current = onAccessGranted;

	const checkStatus = useCallback( async () => {
		const prevHasAccess = hasAccessRef.current;
		const accountStatus = await apiFetch( {
			path: `${ API_NAMESPACE }/ads/account-status`,
		} );

		hasAccessRef.current = accountStatus.has_access;

		// Prevent the completion API and callback from being called unwanted times due to
		// the regain focus or interval timer after access has been granted.
		if (
			prevHasAccess === accountStatus.has_access ||
			accountStatus.has_access !== true
		) {
			return;
		}

		try {
			await onAccessGrantedRef.current();
			receiveAdsAccountStatus( accountStatus );
		} catch ( e ) {
			createNotice(
				'error',
				__(
					'Unable to complete request. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	}, [ receiveAdsAccountStatus, createNotice ] );

	useWindowFocusCallbackIntervalEffect( checkStatus, 30 );
};

export default useAutoCheckAdsAccountStatus;

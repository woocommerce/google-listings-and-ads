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
import { API_NAMESPACE } from '.~/data/constants';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';

const useAutoCheckEnhancedConversionTOS = ( onStatusAccepted = noop ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { receiveAcceptedTerms } = useAppDispatch();
	const prevStatusRef = useRef();

	const onStatusAcceptedRef = useRef();
	onStatusAcceptedRef.current = onStatusAccepted;

	const checkStatus = useCallback( async () => {
		const prevStatus = prevStatusRef.current;
		const tosStatus = await apiFetch( {
			path: `${ API_NAMESPACE }/ads/accepted-customer-data-terms`,
		} );

		prevStatusRef.current = tosStatus.status;

		// Prevent the completion API and callback from being called unwanted times due to
		// the regain focus or interval timer after the status is approved.
		if ( prevStatus === tosStatus.status || tosStatus.status !== true ) {
			return;
		}

		try {
			await onStatusAcceptedRef.current();
			receiveAcceptedTerms( tosStatus );
		} catch ( e ) {
			createNotice(
				'error',
				__(
					'Unable to complete request. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	}, [ receiveAcceptedTerms, createNotice ] );

	useWindowFocusCallbackIntervalEffect( checkStatus, 30 );
};

export default useAutoCheckEnhancedConversionTOS;

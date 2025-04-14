/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { GOOGLE_WPCOM_APP_CONNECTED_STATUS } from '~/constants';
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * Being redirected back from WPCOM app authorization for the product sync (API Pull).
 * This event is only recorded when the user is brought back to this plugin.
 * It won't be recorded if they don't return to this plugin for any reason.
 * (e.g., closing the browser tab).
 *
 * @event gla_product_sync_status_callback
 * @property {string} page Indicates the page where this event happened
 * @property {string} status The authorization status
 */

/**
 * A hook that calls an API to update Google WPCOM app connected status.
 *
 * At the end of the authorization for granting access to Google WPCOM app, Google will
 * redirect back to the merchant site, either the settings page or onboarding setup account page.
 * They will add a query param `google_wpcom_app_status` to the URL, we will store this status to
 * the DB by calling an API `PUT /wc/gla/rest-api/authorize`.
 *
 * @param {string} page The page where this hook is being used. It's used for tracking purposes.
 *
 * @fires gla_product_sync_status_callback with `{ page: 'setup-mc' | 'settings', status: 'approved' | 'disapproved' | 'error' }`
 */
const useUpdateRestAPIAuthorizeStatusByUrlQuery = ( page ) => {
	const { google_wpcom_app_status: googleWPCOMAppStatus, nonce } = getQuery();
	const { invalidateResolution } = useAppDispatch();
	const lockRef = useRef( null );

	const path = `${ API_NAMESPACE }/rest-api/authorize`;
	const [ fetchUpdateRestAPIAuthorize ] = useApiFetchCallback( {
		path,
		method: 'PUT',
	} );

	const handleUpdateRestAPIAuthorize = async () => {
		recordGlaEvent( 'gla_product_sync_status_callback', {
			page,
			status: googleWPCOMAppStatus,
		} );

		try {
			await fetchUpdateRestAPIAuthorize( {
				data: {
					status: googleWPCOMAppStatus,
					nonce,
				},
			} );

			// Refetch Google MC account so we can get the latest gla_wpcom_rest_api_status.
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			// Only show in the console when failed to update rest API authorize status
			// since the user doesn't need to know about it.
			// eslint-disable-next-line no-console
			console.error( e.message );
		}

		// Clean up authorization URL queries anyway
		const url = getNewPath( {
			google_wpcom_app_status: undefined,
			nonce: undefined,
		} );
		getHistory().replace( url );
	};

	if ( lockRef.current === null ) {
		lockRef.current = handleUpdateRestAPIAuthorize;
	}

	useEffect( () => {
		const isValidStatus = Object.values(
			GOOGLE_WPCOM_APP_CONNECTED_STATUS
		).includes( googleWPCOMAppStatus );

		if ( isValidStatus && lockRef.current ) {
			const updateStatus = lockRef.current;
			lockRef.current = false;

			updateStatus();
		}
	}, [ googleWPCOMAppStatus ] );
};

export default useUpdateRestAPIAuthorizeStatusByUrlQuery;

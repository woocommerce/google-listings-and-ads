/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { GOOGLE_WPCOM_APP_CONNECTED_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import { API_NAMESPACE } from '.~/data/constants';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

/**
 * A hook that calls an API to update Google WPCOM app connected status.
 *
 * At the end of the authorization for granting access to Google WPCOM app, Google will
 * redirect back to the merchant site, either the settings page or onboarding setup account page.
 * They will add a query param `google_wpcom_app_status` to the URL, we will store this status to
 * the DB by calling an API `PUT /wc/gla/rest-api/authorize`.
 */
const useUpdateRestAPIAuthorizeStatusByUrlQuery = () => {
	const {
		google_wpcom_app_status: googleWPCOMAppStatus,
		site_nonce: siteNonce,
	} = getQuery();
	const { invalidateResolution } = useAppDispatch();

	const path = `${ API_NAMESPACE }/rest-api/authorize`;
	const [ fetchUpdateRestAPIAuthorize ] = useApiFetchCallback( {
		path,
		method: 'PUT',
	} );

	const handleUpdateRestAPIAuthorize = useCallback( async () => {
		try {
			await fetchUpdateRestAPIAuthorize( {
				data: {
					status: googleWPCOMAppStatus,
					site_nonce: siteNonce,
				},
			} );

			// Refetch Google MC account so we can get the latest gla_wpcom_rest_api_status.
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			// Only show in the console when failed to update rest API authorize status
			// since the user doesn't need to know about it.
			// eslint-disable-next-line no-console
			console.error( e );
		}
	}, [
		fetchUpdateRestAPIAuthorize,
		googleWPCOMAppStatus,
		siteNonce,
		invalidateResolution,
	] );

	useEffect( () => {
		async function updateStatus() {
			await handleUpdateRestAPIAuthorize( googleWPCOMAppStatus );
		}
		if (
			Object.values( GOOGLE_WPCOM_APP_CONNECTED_STATUS ).includes(
				googleWPCOMAppStatus
			)
		) {
			updateStatus();
		}
	}, [ googleWPCOMAppStatus, handleUpdateRestAPIAuthorize ] );
};

export default useUpdateRestAPIAuthorizeStatusByUrlQuery;

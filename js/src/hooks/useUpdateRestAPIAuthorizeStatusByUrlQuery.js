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
 *
 * @param {string} googleWPCOMAuthNonce A nonce stored in the option where it was provided by an Google API when we start the granting Google WPCOM app access flow.
 */
const useUpdateRestAPIAuthorizeStatusByUrlQuery = ( googleWPCOMAuthNonce ) => {
	const {
		google_wpcom_app_status: googleWPCOMAppStatus,
		nonce: nonceFromURLQuery,
	} = getQuery();
	const { invalidateResolution } = useAppDispatch();

	const path = `${ API_NAMESPACE }/rest-api/authorize`;
	const [ fetchUpdateRestAPIAuthorize ] = useApiFetchCallback( {
		path,
		method: 'PUT',
	} );

	const handleUpdateRestAPIAuthorize = useCallback( async () => {
		try {
			if ( ! nonceFromURLQuery && ! googleWPCOMAuthNonce ) {
				return;
			}

			if ( nonceFromURLQuery !== googleWPCOMAuthNonce ) {
				throw new Error(
					'Nonces mismatch when updating REST API authorize status.'
				);
			}

			await fetchUpdateRestAPIAuthorize( {
				data: { status: googleWPCOMAppStatus },
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
		googleWPCOMAuthNonce,
		invalidateResolution,
		nonceFromURLQuery,
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

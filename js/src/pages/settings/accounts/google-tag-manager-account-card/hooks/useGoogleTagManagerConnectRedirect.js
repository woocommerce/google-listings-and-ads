/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

const ERROR_MESSAGE = __(
	'Unable to connect your Google Tag Manager account. Please try again later.',
	'google-listings-and-ads'
);

const CONNECT_PATH = `${ API_NAMESPACE }/tag-manager/connect`;

/**
 * A hook that requests a fresh Google Tag Manager connect URL — granting the
 * `tagmanager.readonly` scope on the already-connected Google account — and redirects the
 * browser to it.
 *
 * @return {{ connect: Function, loading: (boolean|Object) }} Click handler to wire to the action button, and whether a request is in flight (kept truthy through a resolved-but-not-yet-redirected response).
 */
const useGoogleTagManagerConnectRedirect = () => {
	const { createNotice } = useDispatchCoreNotices();

	const [ fetchGoogleTagManagerConnect, { loading, data } ] =
		useApiFetchCallback( { path: CONNECT_PATH } );

	const connect = async () => {
		try {
			const response = await fetchGoogleTagManagerConnect();
			window.location.href = response.url;
		} catch ( error ) {
			createNotice( 'error', ERROR_MESSAGE );
		}
	};

	return { connect, loading: loading || !! data };
};

export default useGoogleTagManagerConnectRedirect;

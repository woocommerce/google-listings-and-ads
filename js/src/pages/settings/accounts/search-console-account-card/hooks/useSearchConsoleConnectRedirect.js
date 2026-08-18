/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

const ERROR_MESSAGE = __(
	'Unable to connect your Search Console account. Please try again later.',
	'google-listings-and-ads'
);

/**
 * A hook that requests a fresh Search Console connect URL and redirects the browser to it.
 *
 * Every caller passes an explicit `next_page_name` — there's no safe default to fall back to,
 * since the backend's own default return destination is unrelated to wherever the connect
 * action was triggered from. The not-connected state's first-time "Connect" action
 * (`ConnectSearchConsoleAccountCard`) sends the merchant into the setup flow; every recovery
 * action (reconnect, retry, resume) sends them back to the Accounts settings page they were
 * already on.
 *
 * @param {Object} query Extra query args to append to the connect URL.
 * @return {{ connect: Function, loading: (boolean|Object) }} Click handler to wire to the action button, and whether a request is in flight (kept truthy through a resolved-but-not-yet-redirected response, matching the original per-component behavior).
 */
const useSearchConsoleConnectRedirect = ( query ) => {
	const { createNotice } = useDispatchCoreNotices();

	const path = addQueryArgs(
		`${ API_NAMESPACE }/search-console/connect`,
		query
	);

	const [ fetchSearchConsoleConnect, { loading, data } ] =
		useApiFetchCallback( { path } );

	const connect = async () => {
		try {
			const response = await fetchSearchConsoleConnect();
			window.location.href = response.url;
		} catch ( error ) {
			createNotice( 'error', ERROR_MESSAGE );
		}
	};

	return { connect, loading: loading || !! data };
};

export default useSearchConsoleConnectRedirect;

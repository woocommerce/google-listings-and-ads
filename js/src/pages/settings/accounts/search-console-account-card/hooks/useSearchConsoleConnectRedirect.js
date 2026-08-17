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
 * Only the not-connected state's first-time "Connect" action (`ConnectSearchConsoleAccountCard`)
 * passes a `query`, adding `next_page_name` so the backend knows which page to return to after
 * OAuth completes. Every other (re)connect action — reconnect, retry, and resume — omits it,
 * since none of them are part of that onboarding return-page flow; `addQueryArgs` leaves the
 * connect URL unchanged when `query` is `undefined`.
 *
 * @param {Object} [query] Extra query args to append to the connect URL.
 * @return {{ onClick: Function, loading: (boolean|Object) }} Click handler to wire to the action button, and whether a request is in flight (kept truthy through a resolved-but-not-yet-redirected response, matching the original per-component behavior).
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

	return { onClick: connect, loading: loading || !! data };
};

export default useSearchConsoleConnectRedirect;

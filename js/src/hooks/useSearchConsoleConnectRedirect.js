/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from './useDispatchCoreNotices';

/**
 * A hook that requests a fresh Search Console connect URL from the backend and redirects the
 * browser to it, showing an error notice if the request fails.
 *
 * Every Search Console card that offers a "(re)connect" action — the initial connect flow,
 * reconnecting after the connection expired, retrying after a failed attempt, and resuming an
 * abandoned flow — goes through this same request/redirect/error sequence and differs only in
 * its error copy and, for the initial connect, an extra query argument telling the backend which
 * page to return to.
 *
 * @param {string} errorMessage The notice shown if requesting the connect URL fails.
 * @param {Object} [query] Optional query args appended to the connect request (e.g. `next_page_name`).
 * @return {{ onClick: Function, loading: (boolean|Object) }} Click handler to wire to the action button, and whether a request is in flight (kept truthy through a resolved-but-not-yet-redirected response, matching the original per-component behavior).
 */
const useSearchConsoleConnectRedirect = ( errorMessage, query ) => {
	const { createNotice } = useDispatchCoreNotices();

	const path = query
		? addQueryArgs( `${ API_NAMESPACE }/search-console/connect`, query )
		: `${ API_NAMESPACE }/search-console/connect`;

	const [ fetchSearchConsoleConnect, { loading, data } ] =
		useApiFetchCallback( { path } );

	const onClick = async () => {
		try {
			const d = await fetchSearchConsoleConnect();
			window.location.href = d.url;
		} catch ( error ) {
			createNotice( 'error', errorMessage );
		}
	};

	return { onClick, loading: loading || data };
};

export default useSearchConsoleConnectRedirect;

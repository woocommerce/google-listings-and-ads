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
	'Unable to connect your Google Search Console account. Please try again later.',
	'google-listings-and-ads'
);

/**
 * A hook that requests a fresh Google Search Console connect URL and redirects the browser to it.
 *
 * The connect endpoint (`src/API/Site/Controllers/SearchConsole/AccountController.php`) takes no
 * query params — it always returns the merchant to the same fixed admin page after OAuth
 * completes, regardless of caller.
 *
 * @return {{ connect: Function, loading: (boolean|Object) }} Click handler to wire to the action button, and whether a request is in flight (kept truthy through a resolved-but-not-yet-redirected response, matching the original per-component behavior).
 */
const useGoogleSearchConsoleConnectRedirect = () => {
	const { createNotice } = useDispatchCoreNotices();

	const [ fetchGoogleSearchConsoleConnect, { loading, data } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/search-console/connect`,
		} );

	const connect = async () => {
		try {
			const response = await fetchGoogleSearchConsoleConnect();
			window.location.href = response.url;
		} catch ( error ) {
			createNotice( 'error', ERROR_MESSAGE );
		}
	};

	return { connect, loading: loading || !! data };
};

export default useGoogleSearchConsoleConnectRedirect;

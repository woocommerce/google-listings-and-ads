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
 * `isInitialConnect` is `true` only for the not-connected state's first-time "Connect" action
 * (`ConnectSearchConsoleAccountCard`), which adds `next_page_name` so the backend knows which
 * page to return to after OAuth completes. It's left `false` (the default) for every other
 * (re)connect action — reconnect, retry, and resume — none of which are part of that onboarding
 * return-page flow, so they request a plain connect URL with no extra query args.
 *
 * @param {boolean} [isInitialConnect] Whether this is the first-time connect flow.
 * @return {{ onClick: Function, loading: (boolean|Object) }} Click handler to wire to the action button, and whether a request is in flight (kept truthy through a resolved-but-not-yet-redirected response, matching the original per-component behavior).
 */
const useSearchConsoleConnectRedirect = ( isInitialConnect = false ) => {
	const { createNotice } = useDispatchCoreNotices();

	const path = addQueryArgs(
		`${ API_NAMESPACE }/search-console/connect`,
		isInitialConnect
			? { next_page_name: 'setup-search-console' }
			: undefined
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

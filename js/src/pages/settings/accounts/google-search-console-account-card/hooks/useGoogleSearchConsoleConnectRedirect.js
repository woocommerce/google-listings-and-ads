/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { redirectTo } from '~/utils/urls';

const ERROR_MESSAGE = __(
	'Unable to connect your Google Search Console account. Please try again later.',
	'google-listings-and-ads'
);

/**
 * A hook that requests a fresh Google Search Console connect URL and redirects the browser to it.
 *
 * @return {{ connect: Function, loading: (boolean|Object) }} Click handler to wire to the action button, and whether a request is in flight (kept truthy through a resolved-but-not-yet-redirected response, matching the original per-component behavior).
 */
const useGoogleSearchConsoleConnectRedirect = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { google } = useGoogleAccount();

	const fetchOption = useMemo(
		() => ( {
			path: addQueryArgs( `${ API_NAMESPACE }/search-console/connect`, {
				login_hint: google?.email,
			} ),
		} ),
		[ google?.email ]
	);

	const [ fetchGoogleSearchConsoleConnect, { loading, data } ] =
		useApiFetchCallback( fetchOption );

	const connect = async () => {
		try {
			const response = await fetchGoogleSearchConsoleConnect();
			redirectTo( response.url );
		} catch ( error ) {
			createNotice( 'error', ERROR_MESSAGE );
		}
	};

	return { connect, loading: loading || !! data };
};

export default useGoogleSearchConsoleConnectRedirect;

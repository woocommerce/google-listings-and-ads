/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';

/**
 * A hook that returns a connect handler that initiates Google Connect with support for incremental OAuth process.
 *
 * The `handleConnect` handler is meant to be used in button click handler. Upon button click, the handler will:
 *
 * 1. Call `fetchGoogleConnect` from `useGoogleAuthorization` hook to get the Google OAuth URL.
 * 2. Redirect the browser to the URL.
 * 3. If there is an error in the above process, it will display an error notice.
 *
 * @param {'setup-mc'|'reconnect'} nextPageName Indicates the next page name mapped to the redirect URL when back from Google authorization.
 * @param {string} [loginHint] Specify the email to be requested additional scopes. Set this parameter only if wants to request a partial oauth to Google.
 * @see https://developers.google.com/identity/protocols/oauth2/openid-connect#login-hint
 * @return {Array} `[ handleConnect, result ]`
 * 		- `handleConnect` is meant to be used as button click handler.
 * 		- `result` is the same returned object from `useApiFetchCallback`.
 */
const useGoogleConnectFlow = ( nextPageName, loginHint ) => {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, result ] = useGoogleAuthorization(
		nextPageName,
		loginHint
	);

	const handleConnect = async () => {
		try {
			const { url } = await fetchGoogleConnect();
			window.location.href = url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Google account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return [ handleConnect, result ];
};

export default useGoogleConnectFlow;

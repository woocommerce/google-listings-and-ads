/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '.~/data/constants';
import useApiFetchCallback from './useApiFetchCallback';

/**
 * Request a Google Oauth URL.
 *
 * @param {'setup-mc'|'setup-ads'|'reconnect'} nextPageName Indicates the next page name mapped to the redirect URL when back from Google authorization.
 * @param {string} [loginHint] Specify the email to be requested additional scopes. Set this parameter only if wants to request a partial oauth to Google.
 * @see https://developers.google.com/identity/protocols/oauth2/openid-connect#login-hint
 * @return {Array} The same structure as `useApiFetchCallback`.
 */
export default function useGoogleAuthorization( nextPageName, loginHint ) {
	const fetchOption = useMemo( () => {
		const query = { next_page_name: nextPageName, login_hint: loginHint };
		const path = addQueryArgs( `${ API_NAMESPACE }/google/connect`, query );
		return { path };
	}, [ nextPageName, loginHint ] );

	return useApiFetchCallback( fetchOption );
}

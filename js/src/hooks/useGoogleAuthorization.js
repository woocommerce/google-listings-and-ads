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
 * @typedef {'setup-mc'|'reconnect'} NextPageName The next page name that maps to the `next` parameter of Google account authorization API.
 */

/**
 * Request a Google Oauth URL.
 *
 * @param {NextPageName} next Indicate the next page name to map the redirect URI when back from Google authorization.
 * @param {string} [loginHint] Specify the email to be requested additional scopes. Set this parameter only if wants to request a partial oauth to Google.
 * @see https://developers.google.com/identity/protocols/oauth2/openid-connect#login-hint
 * @return {Array} The same structure as `useApiFetchCallback`.
 */
export default function useGoogleAuthorization( next, loginHint ) {
	const fetchOption = useMemo( () => {
		const query = { next, login_hint: loginHint };
		const path = addQueryArgs( `${ API_NAMESPACE }/google/connect`, query );
		return { path };
	}, [ next, loginHint ] );

	return useApiFetchCallback( fetchOption );
}

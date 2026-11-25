/**
 * Internal dependencies
 */
import useGoogleAccount from './useGoogleAccount';
import { getAccountAwareUrl } from '~/utils/urls';

/**
 * A hook that resolves an outbound URL to the connected Google account when its email is known,
 * so the merchant doesn't land in a different signed-in account.
 *
 * @param {string} url The destination URL.
 * @return {string} The account-aware URL, or the plain URL when the connected account's email isn't yet known.
 */
const useGoogleAccountAwareUrl = ( url ) => {
	const { google } = useGoogleAccount();

	return getAccountAwareUrl( url, google?.email );
};

export default useGoogleAccountAwareUrl;

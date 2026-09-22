/**
 * Internal dependencies
 */
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { getAccountAwareUrl } from '~/utils/urls';

/**
 * A hook that resolves an outbound Google Tag Manager URL to the connected Google account when
 * its email is known, so the merchant doesn't land in a different signed-in account.
 *
 * @param {string} url The destination Google Tag Manager URL.
 * @return {string} The account-aware URL, or the plain URL when the connected account's email isn't yet known.
 */
const useGoogleTagManagerAccountAwareUrl = ( url ) => {
	const { google } = useGoogleAccount();

	return getAccountAwareUrl( url, google?.email );
};

export default useGoogleTagManagerAccountAwareUrl;

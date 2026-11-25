/**
 * Internal dependencies
 */
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { getAccountAwareUrl } from '~/utils/urls';

/**
 * A hook that builds an outbound Google Search Console URL, resolved to the connected Google
 * account when its email is known so the merchant doesn't land in a different signed-in account.
 *
 * @param {string} [siteUrl] The property's raw Sites API identifier. `undefined` when not yet known.
 * @param {(siteUrl: string) => string} getUrl Builds the destination Google Search Console URL for a given site URL.
 * @return {string|null} The account-aware URL, the plain URL when the connected account's email isn't yet known, or `null` when `siteUrl` isn't set.
 */
const useSearchConsoleAccountAwareUrl = ( siteUrl, getUrl ) => {
	const { google } = useGoogleAccount();

	if ( ! siteUrl ) {
		return null;
	}

	return getAccountAwareUrl( getUrl( siteUrl ), google?.email );
};

export default useSearchConsoleAccountAwareUrl;

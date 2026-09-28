/**
 * Internal dependencies
 */
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { getAccountAwareUrl } from '~/utils/urls';

const GOOGLE_TAG_MANAGER_ACCOUNT_BASE_URL =
	'https://tagmanager.google.com/#/accounts/';

/**
 * A hook that resolves the Google Tag Manager URL for a given account ID to the connected Google
 * account when its email is known, so the merchant doesn't land in a different signed-in account.
 *
 * Takes an account ID rather than reading the connected Google Tag Manager account itself,
 * since it's also used to link out to a not-yet-connected candidate account offered during
 * account selection (`SingleTagManagerAccountNotice`) — not only the connected account.
 *
 * @param {string} accountId The Google Tag Manager account's ID.
 * @return {string} The account-aware URL, or the plain URL when the connected account's email isn't yet known.
 */
const useGoogleTagManagerAccountAwareUrl = ( accountId ) => {
	const { google } = useGoogleAccount();

	return getAccountAwareUrl(
		`${ GOOGLE_TAG_MANAGER_ACCOUNT_BASE_URL }${ accountId }`,
		google?.email
	);
};

export default useGoogleTagManagerAccountAwareUrl;

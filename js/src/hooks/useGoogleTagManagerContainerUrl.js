/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';
import { getGoogleTagManagerContainerUrl } from '~/utils/urls';

/**
 * @typedef { import('~/data/types.js').GoogleTagManagerConnection } GoogleTagManagerConnection
 */

/**
 * A hook returning the URL that opens the connected Google Tag Manager container in Google Tag
 * Manager itself, resolved to the connected Google account when its email is known.
 *
 * @param {GoogleTagManagerConnection|null|undefined} account The Google Tag Manager connection record.
 * @return {string|null} The container's URL, or `null` while no container is connected.
 */
export default function useGoogleTagManagerContainerUrl( account ) {
	const isConnected =
		account?.status === GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED &&
		Boolean( account.id ) &&
		Boolean( account.containerId );

	const containerUrl = isConnected
		? getGoogleTagManagerContainerUrl( account.id, account.containerId )
		: '';
	const accountAwareUrl = useGoogleAccountAwareUrl( containerUrl );

	return isConnected ? accountAwareUrl : null;
}

/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import ConnectedGoogleTagManagerAccountCard from './connected-google-tag-manager-account-card';
import IncompleteGoogleTagManagerAccountCard from './incomplete-google-tag-manager-account-card';
import ConnectGoogleTagManagerAccountCard from './connect-google-tag-manager-account-card';

const { CONNECTED, INCOMPLETE } = GOOGLE_TAG_MANAGER_ACCOUNT_STATUS;

/**
 * Renders the Google Tag Manager account card, driven entirely by the backend-determined
 * connection status: `connected`, `incomplete` (an account has been chosen but its container
 * hasn't), or anything else (not yet connected — covering both zero candidate accounts and
 * account selection). Unlike Search Console, there's no separate "click Connect to begin OAuth"
 * state — Google auth is already established via the existing Merchant Center connection, so
 * account discovery starts automatically.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element|null} The Google Tag Manager account card, or `null` until the connection has resolved.
 */
const GoogleTagManagerAccountCard = ( { onDisconnect } ) => {
	const { account, hasFinishedResolution } = useGoogleTagManagerAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( account?.status === CONNECTED ) {
		return (
			<ConnectedGoogleTagManagerAccountCard
				account={ account }
				onDisconnect={ onDisconnect }
			/>
		);
	}

	if ( account?.status === INCOMPLETE ) {
		return <IncompleteGoogleTagManagerAccountCard />;
	}

	return <ConnectGoogleTagManagerAccountCard />;
};

export default GoogleTagManagerAccountCard;

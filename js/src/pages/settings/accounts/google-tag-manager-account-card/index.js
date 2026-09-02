/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import AllowAccessGoogleTagManagerAccountCard from './allow-access-google-tag-manager-account-card';
import ConnectedGoogleTagManagerAccountCard from './connected-google-tag-manager-account-card';
import IncompleteGoogleTagManagerAccountCard from './incomplete-google-tag-manager-account-card';
import ConnectGoogleTagManagerAccountCard from './connect-google-tag-manager-account-card';

const { CONNECTED, INCOMPLETE } = GOOGLE_TAG_MANAGER_ACCOUNT_STATUS;

/**
 * Renders the Google Tag Manager account card. The connected Google account's OAuth scopes are
 * checked first, ahead of any account/container detection — a merchant who connected their
 * Google account before this feature shipped won't have the `tagmanager.readonly` scope yet. Once
 * that scope is present, the card is driven by the backend-determined connection status:
 * `connected`, `incomplete` (an account has been chosen but its container hasn't), or anything
 * else (not yet connected — covering both zero candidate accounts and account selection).
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element|null} The Google Tag Manager account card, or `null` until the Google account and the connection have resolved.
 */
const GoogleTagManagerAccountCard = ( { onDisconnect } ) => {
	const { scope, hasFinishedResolution: hasResolvedGoogleAccount } =
		useGoogleAccount();
	const { account, hasFinishedResolution: hasResolvedConnection } =
		useGoogleTagManagerAccount();

	if ( ! hasResolvedGoogleAccount ) {
		return null;
	}

	if ( ! scope.gtmRequired ) {
		return <AllowAccessGoogleTagManagerAccountCard />;
	}

	if ( ! hasResolvedConnection ) {
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

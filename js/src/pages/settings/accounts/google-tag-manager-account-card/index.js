/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import ConnectedGoogleTagManagerAccountCard from './connected-google-tag-manager-account-card';
import IncompleteGoogleTagManagerAccountCard from './incomplete-google-tag-manager-account-card';

/**
 * Renders the Google Tag Manager account card, driven entirely by the backend-determined
 * connection state: the connected steady state, and every not-yet-connected sub-state, handled
 * by {@link IncompleteGoogleTagManagerAccountCard}. Unlike Search Console, there's no separate
 * "click Connect to begin OAuth" state — Google auth is already established via the existing
 * Merchant Center connection, so account discovery starts automatically; the not-yet-connected
 * card covers zero-accounts, account-selection, and container-selection alike.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element} The Google Tag Manager account card.
 */
const GoogleTagManagerAccountCard = ( { onDisconnect } ) => {
	const { account } = useGoogleTagManagerAccount();

	if ( account?.status === GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED ) {
		return (
			<ConnectedGoogleTagManagerAccountCard
				account={ account }
				onDisconnect={ onDisconnect }
			/>
		);
	}

	return <IncompleteGoogleTagManagerAccountCard />;
};

export default GoogleTagManagerAccountCard;

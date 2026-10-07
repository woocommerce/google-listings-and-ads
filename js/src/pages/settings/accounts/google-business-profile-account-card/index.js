/**
 * Internal dependencies
 */
import {
	GOOGLE_BUSINESS_PROFILE_ACCOUNT_STATUS,
	GOOGLE_SERVICE,
} from '~/constants';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleBusinessProfileAccount from '~/hooks/useGoogleBusinessProfileAccount';
import FocusableAccountCard from '~/components/focusable-account-card';
import AllowAccessGoogleBusinessProfileAccountCard from './allow-access-google-business-profile-account-card';
import ConnectedGoogleBusinessProfileAccountCard from './connected-google-business-profile-account-card';
import ConnectGoogleBusinessProfileAccountCard from './connect-google-business-profile-account-card';

const { CONNECTED } = GOOGLE_BUSINESS_PROFILE_ACCOUNT_STATUS;

/**
 * Picks the card matching the current scope and connection status.
 *
 * @param {Object} params
 * @param {Object} params.scope The connected Google account's granted scopes.
 * @param {Object} [params.account] The Google Business Profile connection.
 * @param {boolean} params.hasResolvedGoogleAccount Whether the Google account has resolved.
 * @param {boolean} params.hasResolvedConnection Whether the Google Business Profile connection has resolved.
 * @param {() => void} params.onDisconnect Callback when the user clicks to disconnect the Google Business Profile account.
 * @return {JSX.Element|null} The card to render, or `null` while still resolving.
 */
function getCard( {
	scope,
	account,
	hasResolvedGoogleAccount,
	hasResolvedConnection,
	onDisconnect,
} ) {
	if ( ! hasResolvedGoogleAccount ) {
		return null;
	}

	if ( ! scope.gbpRequired ) {
		return <AllowAccessGoogleBusinessProfileAccountCard />;
	}

	if ( ! hasResolvedConnection ) {
		return null;
	}

	if ( account?.status === CONNECTED ) {
		return (
			<ConnectedGoogleBusinessProfileAccountCard
				account={ account }
				onDisconnect={ onDisconnect }
			/>
		);
	}

	return <ConnectGoogleBusinessProfileAccountCard />;
}

/**
 * Renders the Google Business Profile account card. The connected Google account's OAuth scopes
 * are checked first, ahead of any location detection — a merchant who connected their Google
 * account before this feature shipped won't have the `business.manage` scope yet. Once that scope
 * is present, the card is driven by the backend-determined connection status: `connected`, or
 * anything else (not yet connected — covering zero, one, and multiple candidate locations).
 *
 * The chosen card is wrapped once in `FocusableAccountCard`, so it stays mounted when the card
 * switches state.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Business Profile account.
 * @return {JSX.Element|null} The Google Business Profile account card, or `null` until the Google account and the connection have resolved.
 */
const GoogleBusinessProfileAccountCard = ( { onDisconnect } ) => {
	const { scope, hasFinishedResolution: hasResolvedGoogleAccount } =
		useGoogleAccount();
	const { account, hasFinishedResolution: hasResolvedConnection } =
		useGoogleBusinessProfileAccount();

	const card = getCard( {
		scope,
		account,
		hasResolvedGoogleAccount,
		hasResolvedConnection,
		onDisconnect,
	} );

	if ( ! card ) {
		return null;
	}

	return (
		<FocusableAccountCard id={ GOOGLE_SERVICE.BUSINESS_PROFILE }>
			{ card }
		</FocusableAccountCard>
	);
};

export default GoogleBusinessProfileAccountCard;

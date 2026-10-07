/**
 * Internal dependencies
 */
import {
	GOOGLE_SERVICE,
	GOOGLE_BUSINESS_PROFILE_ACCOUNT_STATUS,
} from '~/constants';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import FocusableAccountCard from '~/components/focusable-account-card';
import useGoogleBusinessProfileConnection from './hooks/useGoogleBusinessProfileConnection';
import AllowAccessGoogleBusinessProfileAccountCard from './allow-access-google-business-profile-account-card';
import ConnectGoogleBusinessProfileAccountCard from './connect-google-business-profile-account-card';
import ConnectedGoogleBusinessProfileAccountCard from './connected-google-business-profile-account-card';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileConnection} GoogleBusinessProfileConnection
 */

/**
 * Picks the card matching the granted scopes and the connection status.
 *
 * @param {Object} params
 * @param {Object} params.scope The connected Google Account's granted scopes.
 * @param {boolean} params.hasResolvedGoogleAccount Whether the Google Account has resolved.
 * @param {GoogleBusinessProfileConnection|null} params.connection The Google Business Profile connection.
 * @param {boolean} params.hasResolvedConnection Whether the connection has resolved.
 * @param {() => void} params.onDisconnect Called when the merchant chooses to disconnect.
 * @return {JSX.Element|null} The card to render, or `null` while still resolving.
 */
function getCard( {
	scope,
	hasResolvedGoogleAccount,
	connection,
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

	if (
		connection?.status ===
			GOOGLE_BUSINESS_PROFILE_ACCOUNT_STATUS.CONNECTED &&
		connection.id
	) {
		return (
			<ConnectedGoogleBusinessProfileAccountCard
				location={ connection }
				onDisconnect={ onDisconnect }
			/>
		);
	}

	return <ConnectGoogleBusinessProfileAccountCard />;
}

/**
 * Renders the Google Business Profile account card. Until the connected Google Account carries
 * the Google Business Profile scope, the card asks the merchant to grant it; after that, it shows
 * either the connected location or the steps to connect one. The chosen card is wrapped once in
 * `FocusableAccountCard`, so it stays mounted when the card switches state.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Called when the merchant chooses to disconnect.
 * @return {JSX.Element|null} The Google Business Profile account card, or `null` while still resolving.
 */
const GoogleBusinessProfileAccountCard = ( { onDisconnect } ) => {
	const { scope, hasFinishedResolution: hasResolvedGoogleAccount } =
		useGoogleAccount();
	const { connection, hasFinishedResolution: hasResolvedConnection } =
		useGoogleBusinessProfileConnection();

	const card = getCard( {
		scope,
		hasResolvedGoogleAccount,
		connection,
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

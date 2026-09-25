/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	GOOGLE_TAG_MANAGER_ACCOUNT_STATUS,
	GOOGLE_CONNECTION_OAUTH_PARAM,
	GOOGLE_CONNECTION_OAUTH_CONNECTED,
	GOOGLE_SERVICE_OAUTH_PARAM,
	GOOGLE_SERVICE,
} from '~/constants';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useScrollIntoView from '~/hooks/useScrollIntoView';
import AllowAccessGoogleTagManagerAccountCard from './allow-access-google-tag-manager-account-card';
import ConnectedGoogleTagManagerAccountCard from './connected-google-tag-manager-account-card';
import IncompleteGoogleTagManagerAccountCard from './incomplete-google-tag-manager-account-card';
import ConnectGoogleTagManagerAccountCard from './connect-google-tag-manager-account-card';

const { CONNECTED, INCOMPLETE } = GOOGLE_TAG_MANAGER_ACCOUNT_STATUS;

/**
 * Maps the backend-determined connection status to the card component for that state. Both
 * components ignore the `account`/`onDisconnect` props they don't use; only
 * `ConnectedGoogleTagManagerAccountCard` reads them.
 */
const STATUS_CARD_MAP = {
	[ CONNECTED ]: ConnectedGoogleTagManagerAccountCard,
	[ INCOMPLETE ]: IncompleteGoogleTagManagerAccountCard,
};

/**
 * Picks the card matching the current scope and connection status.
 *
 * @param {Object} params
 * @param {Object} params.scope The connected Google account's granted scopes.
 * @param {Object} [params.account] The Google Tag Manager connection.
 * @param {boolean} params.hasResolvedGoogleAccount Whether the Google account has resolved.
 * @param {boolean} params.hasResolvedConnection Whether the Google Tag Manager connection has resolved.
 * @param {() => void} params.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
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

	if ( ! scope.gtmRequired ) {
		return <AllowAccessGoogleTagManagerAccountCard />;
	}

	if ( ! hasResolvedConnection ) {
		return null;
	}

	const StatusCard = STATUS_CARD_MAP[ account?.status ];

	if ( StatusCard ) {
		return <StatusCard account={ account } onDisconnect={ onDisconnect } />;
	}

	return <ConnectGoogleTagManagerAccountCard />;
}

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
	const { containerRef, scrollIntoView } = useScrollIntoView();

	const query = getQuery();
	const isGoogleTagManagerOAuthReturn =
		query?.[ GOOGLE_CONNECTION_OAUTH_PARAM ] ===
			GOOGLE_CONNECTION_OAUTH_CONNECTED &&
		query?.[ GOOGLE_SERVICE_OAUTH_PARAM ] === GOOGLE_SERVICE.TAG_MANAGER;

	const card = getCard( {
		scope,
		account,
		hasResolvedGoogleAccount,
		hasResolvedConnection,
		onDisconnect,
	} );
	const hasCard = Boolean( card );

	// Wait until a card has actually rendered, so there's something for the ref to scroll to.
	useEffect( () => {
		if ( ! isGoogleTagManagerOAuthReturn || ! hasCard ) {
			return;
		}

		scrollIntoView();
		getHistory().replace(
			getNewPath( {
				[ GOOGLE_CONNECTION_OAUTH_PARAM ]: undefined,
				[ GOOGLE_SERVICE_OAUTH_PARAM ]: undefined,
			} )
		);
	}, [ isGoogleTagManagerOAuthReturn, hasCard, scrollIntoView ] );

	if ( ! card ) {
		return null;
	}

	return <div ref={ containerRef }>{ card }</div>;
};

export default GoogleTagManagerAccountCard;

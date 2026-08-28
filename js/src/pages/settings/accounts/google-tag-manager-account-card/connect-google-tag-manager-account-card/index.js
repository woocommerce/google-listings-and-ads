/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from '../constants';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import useConnectGoogleTagManagerAccount from '../hooks/useConnectGoogleTagManagerAccount';
import Indicator from './indicator';
import AccountSelection from './account-selection';

/**
 * Renders the Google Tag Manager account card for the not-yet-connected state: the zero-accounts
 * CTA, single-account auto-select, and multi-account selection, culminating in the "Connect"
 * action. Once an account is connected, `IncompleteGoogleTagManagerAccountCard` takes over for
 * the remaining container-selection step.
 *
 * The picked account and its "Connect" submit action are owned here (not inside
 * `AccountSelection` itself) because "Connect" renders in the `indicator` slot, not inline next
 * to the selector — `Indicator` and `AccountSelection` are siblings, so the value they both need
 * has to live in their common parent. The single-candidate auto-select lives here for the same
 * reason: it's this component's own `accountId` state being set.
 *
 * @return {JSX.Element} The account card.
 */
const ConnectGoogleTagManagerAccountCard = () => {
	const { existingAccounts } = useExistingGoogleTagManagerAccounts();
	const [ accountId, setAccountId ] = useState();
	const { connect, loading: isConnecting } =
		useConnectGoogleTagManagerAccount();

	// With only one candidate there's nothing to pick — auto-select it so "Connect" enables
	// without showing a selector that only ever has one option.
	useEffect( () => {
		if ( existingAccounts?.length !== 1 ) {
			return;
		}

		setAccountId( existingAccounts[ 0 ].id );
	}, [ existingAccounts ] );

	const handleConnectClick = () => connect( accountId );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				<Indicator
					accountId={ accountId }
					isConnecting={ isConnecting }
					onConnectClick={ handleConnectClick }
				/>
			}
			detail={
				<AccountSelection
					accountId={ accountId }
					onAccountChange={ setAccountId }
				/>
			}
			expandedDetail
		/>
	);
};

export default ConnectGoogleTagManagerAccountCard;

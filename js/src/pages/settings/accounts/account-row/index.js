/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import AccountActions from './account-actions';
import Indicator from './indicator';
import './index.scss';

/**
 * @typedef {import('../useConnectedAccounts').ConnectedAccountItem} ConnectedAccountItem
 */

/**
 * Renders a single account as a generic row inside a group card.
 *
 * @param {Object} props Component props.
 * @param {ConnectedAccountItem} props.account Account item.
 * @param {(target: string) => void} props.onDisconnect Called with the account's disconnect-modal target when the Disconnect action is chosen.
 * @return {JSX.Element} The account row.
 */
export default function AccountRow( { account, onDisconnect } ) {
	const actions = (
		<AccountActions account={ account } onDisconnect={ onDisconnect } />
	);

	return (
		<AccountCard
			appearance={ account.appearance }
			title={ account.title }
			description={ account.description }
			detail={ account.detail }
			indicator={ <Indicator account={ account } actions={ actions } /> }
			role="listitem"
			className="gla-account-row"
		/>
	);
}

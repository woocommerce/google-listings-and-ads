/**
 * External dependencies
 */
import {
	ExternalLink,
	__experimentalItem as Item,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { appearanceDict } from '~/components/account-card';
import AccountActions from './account-actions';
import RowIndicator from './row-indicator';

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
	const icon = appearanceDict[ account.appearance ]?.icon;

	return (
		<Item className="gla-connected-accounts__row">
			<div className="gla-connected-accounts__logo">{ icon }</div>
			<div className="gla-connected-accounts__subject">
				<div className="gla-connected-accounts__title">
					{ account.title }
				</div>
				<div className="gla-connected-accounts__description">
					{ account.description }
				</div>
				{ account.helper && (
					<div className="gla-connected-accounts__detail gla-connected-accounts__detail--link">
						{ account.helper }
					</div>
				) }
				{ account.detail && (
					<div className="gla-connected-accounts__detail">
						{ account.detailUrl ? (
							<ExternalLink href={ account.detailUrl }>
								{ account.detail }
							</ExternalLink>
						) : (
							account.detail
						) }
					</div>
				) }
			</div>
			<div className="gla-connected-accounts__indicator">
				<RowIndicator account={ account } actions={ actions } />
			</div>
		</Item>
	);
}

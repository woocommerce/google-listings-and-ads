/**
 * External dependencies
 */
import {
	ExternalLink,
	Flex,
	FlexBlock,
	FlexItem,
	__experimentalItem as Item,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { appearanceDict } from '~/components/account-card';
import AccountActions from './account-actions';
import RowIndicator from './row-indicator';
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
	const icon = appearanceDict[ account.appearance ]?.icon;

	return (
		<Item className="gla-account-row">
			<Flex align="flex-start" gap={ 4 } wrap>
				<FlexItem>{ icon }</FlexItem>
				<FlexBlock>
					<div className="gla-account-row__title">
						{ account.title }
					</div>
					<div className="gla-account-row__description">
						{ account.description }
					</div>
					{ account.helper && (
						<div className="gla-account-row__detail gla-account-row__detail--link">
							{ account.helper }
						</div>
					) }
					{ account.detail && (
						<div className="gla-account-row__detail">
							{ account.detailUrl ? (
								<ExternalLink href={ account.detailUrl }>
									{ account.detail }
								</ExternalLink>
							) : (
								account.detail
							) }
						</div>
					) }
				</FlexBlock>
				<FlexItem className="gla-account-row__status-action">
					<RowIndicator account={ account } actions={ actions } />
				</FlexItem>
			</Flex>
		</Item>
	);
}

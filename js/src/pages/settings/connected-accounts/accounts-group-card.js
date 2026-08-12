/**
 * External dependencies
 */
import { __experimentalItemGroup as ItemGroup } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AccountRow from './account-row';
import './accounts-group-card.scss';

/**
 * Renders one group of accounts as a single Card: a header (title +
 * description) followed by the account rows, separated by dividers.
 *
 * @param {Object} props Component props.
 * @param {string} props.title Group title.
 * @param {string} props.description Group description.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem[]} props.accounts Accounts in this group.
 * @param {(target: string) => void} props.onDisconnect Called with the account's disconnect-modal target when a Disconnect action is chosen.
 * @return {JSX.Element} The group card.
 */
export default function AccountsGroupCard( {
	title,
	description,
	accounts,
	onDisconnect,
} ) {
	return (
		<Section.Card className="gla-accounts-group-card">
			<Section.Card.Body>
				<div className="gla-accounts-group-card__header">
					<h3 className="gla-accounts-group-card__title">
						{ title }
					</h3>
					<p className="gla-accounts-group-card__description">
						{ description }
					</p>
				</div>
				<ItemGroup isSeparated>
					{ accounts.map( ( account ) => {
						const RowComponent = account.RowComponent || AccountRow;

						return (
							<RowComponent
								key={ account.id }
								account={ account }
								onDisconnect={ onDisconnect }
							/>
						);
					} ) }
				</ItemGroup>
			</Section.Card.Body>
		</Section.Card>
	);
}

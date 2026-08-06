/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';

/**
 * @typedef {import('../useConnectedAccounts').ConnectedAccountItem} ConnectedAccountItem
 */

/**
 * Renders the destructive action inside an account actions menu.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onClose Closes the dropdown menu.
 * @param {(target: string) => void} props.onDisconnect Opens the disconnect flow.
 * @param {string} props.disconnectTarget Disconnect-modal target.
 * @param {string} props.disconnectLabel Accessible action label.
 * @return {JSX.Element} The disconnect menu item.
 */
function DisconnectMenuItem( {
	onClose,
	onDisconnect,
	disconnectTarget,
	disconnectLabel,
} ) {
	const handleClick = () => {
		onClose();
		onDisconnect( disconnectTarget );
	};

	return (
		<MenuGroup>
			<MenuItem isDestructive onClick={ handleClick }>
				{ disconnectLabel }
			</MenuItem>
		</MenuGroup>
	);
}

/**
 * Renders the per-account actions menu when an individual disconnect is supported.
 *
 * @param {Object} props Component props.
 * @param {ConnectedAccountItem} props.account Account item.
 * @param {(target: string) => void} props.onDisconnect Called with the account's disconnect-modal target when the Disconnect action is chosen.
 * @return {JSX.Element|null} The actions menu, if available.
 */
export default function AccountActions( { account, onDisconnect } ) {
	if ( ! account.canDisconnect ) {
		return null;
	}

	const accountActionsLabel = sprintf(
		/* translators: %s: account title, for example "YouTube". */
		__( 'Account actions for %s', 'google-listings-and-ads' ),
		account.title
	);
	const disconnectLabel = __( 'Disconnect', 'google-listings-and-ads' );

	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ accountActionsLabel }
			popoverProps={ { placement: 'bottom-end' } }
		>
			{ ( { onClose } ) => (
				<DisconnectMenuItem
					onClose={ onClose }
					onDisconnect={ onDisconnect }
					disconnectTarget={ account.disconnectTarget }
					disconnectLabel={ disconnectLabel }
				/>
			) }
		</DropdownMenu>
	);
}

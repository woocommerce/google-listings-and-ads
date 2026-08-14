/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import DisconnectMenuItem from './disconnect-menu-item';

/**
 * Renders the per-account actions menu when an individual disconnect is supported.
 *
 * @param {Object} props Component props.
 * @param {string} props.accountTitle Account title.
 * @param {(target: string) => void} props.onDisconnect Called with the account's disconnect-modal target when the Disconnect action is chosen.
 * @param {JSX.Element|JSX.Element[]} [props.children] Additional menu items to render.
 * @return {JSX.Element|null} The actions menu, if available.
 */
export default function AccountCardActions( {
	accountTitle,
	onDisconnect,
	children,
} ) {
	const accountActionsLabel = sprintf(
		/* translators: %s: account title, for example "YouTube". */
		__( 'Account actions for %s', 'google-listings-and-ads' ),
		accountTitle
	);

	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ accountActionsLabel }
			popoverProps={ { placement: 'bottom-end' } }
		>
			{ ( { onClose } ) => {
				return (
					<MenuGroup>
						{ children }

						{ onDisconnect && (
							<DisconnectMenuItem
								onClose={ onClose }
								onDisconnect={ onDisconnect }
							/>
						) }
					</MenuGroup>
				);
			} }
		</DropdownMenu>
	);
}

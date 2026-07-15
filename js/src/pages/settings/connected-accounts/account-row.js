/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem, Flex } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import YouTubeConnectButton from './youtube-connect-button';
import { ACCOUNT_LOGOS } from './account-logos';

/**
 * Renders the right-hand side of an account row: the connection status (a
 * "Connected" badge with an optional actions menu) or a connect action.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {() => void} props.onDisconnect Called when the (Ads-only) Disconnect action is chosen.
 * @return {JSX.Element} The row indicator.
 */
function RowIndicator( { account, onDisconnect } ) {
	if ( ! account.connected ) {
		// Only YouTube can currently be in a not-connected state within these
		// groups; render its connect action.
		if ( account.id === 'youtube' ) {
			return <YouTubeConnectButton />;
		}
		return null;
	}

	return (
		<Flex align="center" gap={ 3 } justify="flex-end">
			<Badge intent="success">
				{ __( 'Connected', 'google-listings-and-ads' ) }
			</Badge>
			{ account.canDisconnect && (
				<DropdownMenu
					icon={ moreVertical }
					label={ __( 'Account actions', 'google-listings-and-ads' ) }
					popoverProps={ { placement: 'bottom-end' } }
				>
					{ ( { onClose } ) => (
						<MenuGroup>
							<MenuItem
								isDestructive
								onClick={ () => {
									onClose();
									onDisconnect();
								} }
							>
								{ __(
									'Disconnect',
									'google-listings-and-ads'
								) }
							</MenuItem>
						</MenuGroup>
					) }
				</DropdownMenu>
			) }
		</Flex>
	);
}

/**
 * Renders a single account as a row inside a group card: logo, title,
 * description and detail on the left; status or connect action on the right.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {() => void} props.onDisconnect Called when the (Ads-only) Disconnect action is chosen.
 * @return {JSX.Element} The account row.
 */
export default function AccountRow( { account, onDisconnect } ) {
	return (
		<div className="gla-connected-accounts__row">
			<img
				className="gla-connected-accounts__logo"
				src={ ACCOUNT_LOGOS[ account.appearance ] }
				alt=""
				width="40"
				height="40"
			/>
			<div className="gla-connected-accounts__subject">
				<div className="gla-connected-accounts__title">
					{ account.title }
				</div>
				<div className="gla-connected-accounts__description">
					{ account.description }
				</div>
				{ account.detail && (
					<div className="gla-connected-accounts__detail">
						{ account.detail }
					</div>
				) }
			</div>
			<div className="gla-connected-accounts__indicator">
				<RowIndicator
					account={ account }
					onDisconnect={ onDisconnect }
				/>
			</div>
		</div>
	);
}

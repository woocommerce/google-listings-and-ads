/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Flex,
	ExternalLink,
	__experimentalItem as Item,
} from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import YouTubeMerchantTermsLink from '~/components/youtube-merchant-terms-link';
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';
import YouTubeConnectButton from './youtube-connect-button';
import MerchantCenterConnectButton from './merchant-center-connect-button';
import { ACCOUNT_LOGOS } from './account-logos';
import IncompleteYouTubeAccountRow from './incomplete-youtube-account-row';

/**
 * Renders the per-account actions menu when an individual disconnect is supported.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {(target: string) => void} props.onDisconnect Called with the account's disconnect-modal target when the Disconnect action is chosen.
 * @return {JSX.Element|null} The actions menu, if available.
 */
function AccountActions( { account, onDisconnect } ) {
	if ( ! account.canDisconnect ) {
		return null;
	}

	const accountActionsLabel = sprintf(
		/* translators: %s: account title, for example "YouTube". */
		__( 'Account actions for %s', 'google-listings-and-ads' ),
		account.title
	);
	const disconnectLabel = sprintf(
		/* translators: %s: account title, for example "YouTube". */
		__( 'Disconnect %s', 'google-listings-and-ads' ),
		account.title
	);

	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ accountActionsLabel }
			popoverProps={ { placement: 'bottom-end' } }
		>
			{ ( { onClose } ) => (
				<MenuGroup>
					<MenuItem
						isDestructive
						onClick={ () => {
							onClose();
							onDisconnect( account.disconnectTarget );
						} }
					>
						{ disconnectLabel }
					</MenuItem>
				</MenuGroup>
			) }
		</DropdownMenu>
	);
}

/**
 * Renders the right-hand side of an account row: the connection status (a
 * "Connected" badge with an optional actions menu) or a connect action.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {JSX.Element|null} props.actions Account actions menu.
 * @return {JSX.Element} The row indicator.
 */
function RowIndicator( { account, actions } ) {
	if ( ! account.connected ) {
		// Render the account's in-page connect action, where one is offered.
		if ( account.id === 'youtube' ) {
			return <YouTubeConnectButton />;
		}
		if ( account.id === 'merchant-center' ) {
			return <MerchantCenterConnectButton />;
		}
		return null;
	}

	return (
		<Flex align="center" gap={ 3 } justify="flex-end">
			<Badge intent="success">
				{ __( 'Connected', 'google-listings-and-ads' ) }
			</Badge>
			{ actions }
		</Flex>
	);
}

/**
 * Renders a single account as a row inside a group card: logo, title,
 * description and detail on the left; status or connect action on the right.
 *
 * @param {Object} props Component props.
 * @param {import('./useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @param {(target: string) => void} props.onDisconnect Called with the account's disconnect-modal target when the Disconnect action is chosen.
 * @return {JSX.Element} The account row.
 */
export default function AccountRow( { account, onDisconnect } ) {
	const actions = (
		<AccountActions account={ account } onDisconnect={ onDisconnect } />
	);

	if (
		account.id === 'youtube' &&
		account.status === YOUTUBE_ACCOUNT_STATUS.INCOMPLETE
	) {
		return (
			<IncompleteYouTubeAccountRow
				account={ {
					...account,
					logo: ACCOUNT_LOGOS[ account.appearance ],
				} }
				actions={ actions }
			/>
		);
	}

	return (
		<Item className="gla-connected-accounts__row">
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
				{ account.id === 'youtube' && ! account.connected && (
					<div className="gla-connected-accounts__detail gla-connected-accounts__detail--link">
						<YouTubeMerchantTermsLink context="settings-connect-youtube-account-card" />
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

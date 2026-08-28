/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ConnectedBadge from '../connected-badge';
import AccountCardActions from '../account-card-actions';
import { getGoogleTagManagerAccountUrl } from '~/utils/urls';

/**
 * @typedef { import('~/data/types.js').GoogleTagManagerConnection } GoogleTagManagerConnection
 */

/**
 * Renders the connected indicator for the Google Tag Manager account card, including the
 * connected badge and the account actions menu with its "Open Google Tag Manager" action and
 * its "Disconnect" action.
 *
 * @param {Object} props Component props.
 * @param {GoogleTagManagerConnection} props.account The connected Google Tag Manager connection record.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Tag Manager account.
 * @return {JSX.Element} The connected indicator for the Google Tag Manager account card.
 */
const ConnectedIndicator = ( { account, onDisconnect } ) => {
	return (
		<Flex>
			<FlexItem>
				<ConnectedBadge />
			</FlexItem>
			<FlexItem>
				<AccountCardActions
					accountTitle={ __(
						'Google Tag Manager',
						'google-listings-and-ads'
					) }
					onDisconnect={ onDisconnect }
				>
					<MenuItem
						href={ getGoogleTagManagerAccountUrl( account.id ) }
						target="_blank"
						rel="noreferrer"
					>
						{ __(
							'Open Google Tag Manager',
							'google-listings-and-ads'
						) }
					</MenuItem>
				</AccountCardActions>
			</FlexItem>
		</Flex>
	);
};

export default ConnectedIndicator;

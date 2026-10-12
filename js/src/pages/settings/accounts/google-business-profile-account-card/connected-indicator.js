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
import useGoogleAccountAwareUrl from '~/hooks/useGoogleAccountAwareUrl';

/**
 * @typedef { import('~/data/types.js').GoogleBusinessProfileConnection } GoogleBusinessProfileConnection
 */

/**
 * Renders the connected indicator for the Google Business Profile account card, including the
 * connected badge and the account actions menu with its "View Google Business Profile" action and
 * its "Disconnect" action. "View Google Business Profile" is left out when the connected location
 * has no listing URL.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileConnection} props.account The connected Google Business Profile connection record.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Business Profile account.
 * @return {JSX.Element} The connected indicator for the Google Business Profile account card.
 */
const ConnectedIndicator = ( { account, onDisconnect } ) => {
	const accountAwareUrl = useGoogleAccountAwareUrl( account.mapsUri );

	return (
		<Flex>
			<FlexItem>
				<ConnectedBadge />
			</FlexItem>
			<FlexItem>
				<AccountCardActions
					accountTitle={ __(
						'Google Business Profile',
						'google-listings-and-ads'
					) }
					onDisconnect={ onDisconnect }
				>
					{ account.mapsUri && (
						<MenuItem
							href={ accountAwareUrl }
							target="_blank"
							rel="noreferrer"
						>
							{ __(
								'View Google Business Profile',
								'google-listings-and-ads'
							) }
						</MenuItem>
					) }
				</AccountCardActions>
			</FlexItem>
		</Flex>
	);
};

export default ConnectedIndicator;

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

/**
 * Renders the connected indicator for the Google Business Profile account card, including the
 * connected badge and the account actions menu with its "View Google Business Profile" action and
 * its "Disconnect" action. "View Google Business Profile" is left out when the connected location
 * has no listing URL.
 *
 * @param {Object} props Component props.
 * @param {string} [props.url] The account-aware URL to the listing on Google, if it has one.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Business Profile account.
 * @return {JSX.Element} The connected indicator for the Google Business Profile account card.
 */
const ConnectedIndicator = ( { url, onDisconnect } ) => {
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
					{ url && (
						<MenuItem href={ url } target="_blank" rel="noreferrer">
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

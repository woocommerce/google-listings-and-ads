/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { geReportsUrl } from '~/utils/urls';
import ConnectedBadge from '../connected-badge';
import AccountCardActions from '../account-card-actions';

// The Reports page has no dedicated "Organic search" sub-view yet, so this links to the general
// Reports page for now — swap in a deep link once that sub-view exists.
const REPORTS_URL = geReportsUrl();

/**
 * Renders the connected indicator for the Google Search Console account card, including the connected
 * badge and the account actions menu with its "View Organic Search report" action and its
 * "Disconnect" action.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element} The connected indicator for the Google Search Console account card.
 */
const ConnectedIndicator = ( { onDisconnect } ) => {
	return (
		<Flex>
			<FlexItem>
				<ConnectedBadge />
			</FlexItem>
			<FlexItem>
				<AccountCardActions
					accountTitle={ __(
						'Google Search Console',
						'google-listings-and-ads'
					) }
					onDisconnect={ onDisconnect }
				>
					<MenuItem href={ REPORTS_URL }>
						{ __(
							'View Organic Search report',
							'google-listings-and-ads'
						) }
					</MenuItem>
				</AccountCardActions>
			</FlexItem>
		</Flex>
	);
};

export default ConnectedIndicator;

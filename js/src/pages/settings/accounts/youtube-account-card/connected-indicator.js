/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ConnectedBadge from '../connected-badge';
import AccountCardActions from '../account-card-actions';

/**
 * Renders the connected indicator for the YouTube account card, including the connected badge and the account actions menu.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the YouTube account.
 * @return {JSX.Element} The connected indicator for the YouTube account card.
 */
const ConnectedIndicator = ( { onDisconnect } ) => {
	return (
		<Flex>
			<FlexItem>
				<ConnectedBadge />
			</FlexItem>
			<FlexItem>
				<AccountCardActions
					accountTitle={ __( 'YouTube', 'google-listings-and-ads' ) }
					onDisconnect={ onDisconnect }
				/>
			</FlexItem>
		</Flex>
	);
};

export default ConnectedIndicator;

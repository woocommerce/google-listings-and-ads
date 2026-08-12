/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';

/**
 * @typedef {import('../useConnectedAccounts').ConnectedAccountItem} ConnectedAccountItem
 */

/**
 * Renders the right-hand side of an account row: the connection status and
 * optional actions, or the account's supplied connect action.
 *
 * @param {Object} props Component props.
 * @param {ConnectedAccountItem} props.account Account item.
 * @param {JSX.Element|null} props.actions Account actions menu.
 * @return {JSX.Element|null} The row indicator, if available.
 */
export default function RowIndicator( { account, actions } ) {
	const { connected, ConnectComponent } = account;

	if ( ! connected ) {
		return ConnectComponent ? <ConnectComponent /> : null;
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

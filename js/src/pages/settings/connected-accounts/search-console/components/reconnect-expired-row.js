/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectActionRow from './connect-action-row';
import { errorDescription } from './search-console-error-row';

/**
 * Renders the "reconnect" state, shown when the Search Console connection has expired.
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The row.
 */
export default function ReconnectExpiredRow( { account } ) {
	return (
		<ConnectActionRow
			account={ account }
			isError
			description={ errorDescription(
				__(
					'<alert>Connection expired:</alert> Your Search Console connection needs to be re-authorized.',
					'google-listings-and-ads'
				)
			) }
			buttonLabel={ __( 'Reconnect', 'google-listings-and-ads' ) }
		/>
	);
}

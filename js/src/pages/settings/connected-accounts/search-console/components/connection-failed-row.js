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
 * Renders the "connection failed" state, shown when the initial connect attempt failed.
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The row.
 */
export default function ConnectionFailedRow( { account } ) {
	return (
		<ConnectActionRow
			account={ account }
			isError
			description={ errorDescription(
				__(
					"<alert>Connection failed:</alert> We couldn't connect your Search Console account. Please try again.",
					'google-listings-and-ads'
				)
			) }
			buttonLabel={ __( 'Retry', 'google-listings-and-ads' ) }
		/>
	);
}

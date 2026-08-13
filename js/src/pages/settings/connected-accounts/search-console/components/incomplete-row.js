/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectActionRow from './connect-action-row';

/**
 * Renders the generic fallback for an abandoned connect flow that isn't covered by a more
 * specific step.
 *
 * @param {Object} props Component props.
 * @param {import('../../useConnectedAccounts').ConnectedAccountItem} props.account Account item.
 * @return {JSX.Element} The row.
 */
export default function IncompleteRow( { account } ) {
	return (
		<ConnectActionRow
			account={ account }
			description={ __(
				"Your Search Console connection isn't complete yet.",
				'google-listings-and-ads'
			) }
			buttonLabel={ __( 'Resume setup', 'google-listings-and-ads' ) }
		/>
	);
}

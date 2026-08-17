/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SearchConsoleConnectActionCard from './connect-action-card';
import { errorDescription } from './error-account-card';

/**
 * Renders the "reconnect" state, shown when the Search Console connection has expired.
 *
 * @return {JSX.Element} The account card.
 */
export default function ReconnectSearchConsoleAccountCard() {
	return (
		<SearchConsoleConnectActionCard
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

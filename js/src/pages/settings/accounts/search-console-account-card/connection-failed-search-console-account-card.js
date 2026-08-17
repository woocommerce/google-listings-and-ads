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
 * Renders the "connection failed" state, shown when the initial connect attempt failed.
 *
 * @return {JSX.Element} The account card.
 */
export default function ConnectionFailedSearchConsoleAccountCard() {
	return (
		<SearchConsoleConnectActionCard
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

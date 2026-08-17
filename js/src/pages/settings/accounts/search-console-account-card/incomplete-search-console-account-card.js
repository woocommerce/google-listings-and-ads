/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SearchConsoleConnectActionCard from './connect-action-card';

/**
 * Renders the generic fallback for an abandoned connect flow that isn't covered by a more
 * specific step.
 *
 * @return {JSX.Element} The account card.
 */
export default function IncompleteSearchConsoleAccountCard() {
	return (
		<SearchConsoleConnectActionCard
			description={ __(
				"Your Search Console connection isn't complete yet.",
				'google-listings-and-ads'
			) }
			buttonLabel={ __( 'Resume setup', 'google-listings-and-ads' ) }
		/>
	);
}

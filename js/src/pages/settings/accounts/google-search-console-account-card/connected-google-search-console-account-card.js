/**
 * External dependencies
 */
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AccountCardTextDetail from '../account-card-text-detail';
import { GOOGLE_SEARCH_CONSOLE_DESCRIPTION } from './constants';
import ConnectedIndicator from './connected-indicator';
import ConnectedSuccessNotice from './connected-success-notice';

/**
 * @typedef { import('~/data/types.js').GoogleSearchConsoleAccount } GoogleSearchConsoleAccount
 */

/**
 * Builds the outbound link to a property in Google Search Console itself (not this plugin's
 * own Reports page).
 *
 * @param {string} siteUrl The property's raw Sites API identifier.
 * @return {string} The Google Search Console URL for that property.
 */
const getSearchConsolePropertyUrl = ( siteUrl ) =>
	`https://search.google.com/search-console?resource_id=${ encodeURIComponent(
		siteUrl
	) }`;

/**
 * Renders the connected Google Search Console account card: a "Connected" badge, an actions
 * menu offering "View Organic Search report", a link to the connected property in Google
 * Search Console itself, and — immediately after an auto-resolved connection — a one-time
 * success notice.
 *
 * `site_url` and `just_resolved` are a proposed backend addition, not yet sent by the real
 * backend — this degrades to no property link and no success notice until that lands.
 *
 * @param {Object} props Component props.
 * @param {GoogleSearchConsoleAccount} props.account The connected Google Search Console account.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the Google Search Console account.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleSearchConsoleAccountCard = ( {
	account,
	onDisconnect,
} ) => {
	const siteUrl = account.site_url;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ GOOGLE_SEARCH_CONSOLE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				siteUrl ? (
					<AccountCardTextDetail>
						<ExternalLink
							href={ getSearchConsolePropertyUrl( siteUrl ) }
						>
							{ siteUrl }
						</ExternalLink>
					</AccountCardTextDetail>
				) : null
			}
			indicator={ <ConnectedIndicator onDisconnect={ onDisconnect } /> }
		>
			{ account.just_resolved && <ConnectedSuccessNotice /> }
		</AccountCard>
	);
};

export default ConnectedGoogleSearchConsoleAccountCard;

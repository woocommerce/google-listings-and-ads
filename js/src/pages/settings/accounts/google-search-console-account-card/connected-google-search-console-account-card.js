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
import useSearchConsoleAccountAwareUrl from './hooks/useSearchConsoleAccountAwareUrl';
import { getSearchConsolePropertyUrl } from '~/utils/urls';

/**
 * @typedef { import('~/data/types.js').GoogleSearchConsoleAccount } GoogleSearchConsoleAccount
 */

/**
 * Component to render the connected Google Search Console account card
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
	const accountAwarePropertyUrl = useSearchConsoleAccountAwareUrl(
		siteUrl,
		getSearchConsolePropertyUrl
	);

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ GOOGLE_SEARCH_CONSOLE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				siteUrl ? (
					<AccountCardTextDetail>
						<ExternalLink href={ accountAwarePropertyUrl }>
							{ siteUrl }
						</ExternalLink>
					</AccountCardTextDetail>
				) : null
			}
			indicator={
				<ConnectedIndicator
					account={ account }
					onDisconnect={ onDisconnect }
				/>
			}
		>
			{ account.just_resolved && <ConnectedSuccessNotice /> }
		</AccountCard>
	);
};

export default ConnectedGoogleSearchConsoleAccountCard;

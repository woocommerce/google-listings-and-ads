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

/**
 * @typedef { import('~/data/types.js').GoogleSearchConsoleAccount } GoogleSearchConsoleAccount
 */

/**
 * Renders the connected Google Search Console account card: the connected property link, a "Connected"
 * badge, and an actions menu offering "View Organic Search report".
 *
 * @param {Object} props Component props.
 * @param {GoogleSearchConsoleAccount} props.account The connected Google Search Console account.
 * @return {JSX.Element} The account card.
 */
const ConnectedGoogleSearchConsoleAccountCard = ( { account } ) => {
	const propertyUrl = account.property?.url;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ GOOGLE_SEARCH_CONSOLE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				propertyUrl ? (
					<AccountCardTextDetail>
						<ExternalLink href={ propertyUrl }>
							{ propertyUrl }
						</ExternalLink>
					</AccountCardTextDetail>
				) : null
			}
			indicator={ <ConnectedIndicator /> }
		/>
	);
};

export default ConnectedGoogleSearchConsoleAccountCard;

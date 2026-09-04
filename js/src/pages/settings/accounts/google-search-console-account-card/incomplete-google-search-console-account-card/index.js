/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import { GOOGLE_SEARCH_CONSOLE_DESCRIPTION } from '../constants';
import Indicator from './indicator';
import Detail from './detail';

/**
 * Renders the Google Search Console account card for every incomplete connect-flow sub-state —
 * property selection, verification, action-needed (verification lost), reconnect (connection
 * expired), connection-failed (initial attempt failed), and a generic resume fallback for an
 * abandoned flow that isn't covered by a more specific step. All of these share the same
 * `AccountCard` layout, varying only the `indicator` and `detail` content for the current step.
 * `Indicator` and `Detail` are self-contained and read the account directly.
 *
 * @return {JSX.Element} The account card.
 */
const IncompleteGoogleSearchConsoleAccountCard = () => {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ GOOGLE_SEARCH_CONSOLE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={ <Indicator /> }
			detail={ <Detail /> }
			expandedDetail
		/>
	);
};

export default IncompleteGoogleSearchConsoleAccountCard;

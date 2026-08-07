/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';

/**
 * @typedef { import('~/hooks/useSearchConsoleAccount.js').SearchConsoleAccount } SearchConsoleAccount
 */

/**
 * Renders a minimal Search Console account card with connected account information.
 *
 * Rendered only once the connection is fully complete — i.e. the backend has resolved a
 * property and verification has succeeded (AC-017, AC-021).
 *
 * @param {Object} props React props.
 * @param {SearchConsoleAccount} props.searchConsoleAccount The connected Search Console account.
 */
const ConnectedSearchConsoleAccountCard = ( { searchConsoleAccount } ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ searchConsoleAccount.property?.url }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
};

export default ConnectedSearchConsoleAccountCard;

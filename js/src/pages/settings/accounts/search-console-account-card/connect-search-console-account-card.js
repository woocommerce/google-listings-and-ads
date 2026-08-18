/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import useSearchConsoleConnectRedirect from './hooks/useSearchConsoleConnectRedirect';
import { SEARCH_CONSOLE_DESCRIPTION } from './constants';

/**
 * Clicking on the button to connect the Search Console account.
 *
 * @event gla_search_console_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the not-connected Search Console account card.
 *
 * The Google auth-prompt-skip behavior (when the merchant already has a Merchant Center
 * connection) is handled entirely by the backend redirect target — this card only requests the
 * connect URL and follows it.
 *
 * @fires gla_search_console_account_connect_button_click
 *
 * @return {JSX.Element} The account card.
 */
export default function ConnectSearchConsoleAccountCard() {
	const { connect: handleConnectClick, loading } =
		useSearchConsoleConnectRedirect( {
			next_page_name: 'setup-search-console',
		} );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ SEARCH_CONSOLE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				<AppButton
					eventName="gla_search_console_account_connect_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleConnectClick }
					loading={ loading }
					isSecondary
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
}

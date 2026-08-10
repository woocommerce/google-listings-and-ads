/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import useSearchConsoleConnectRedirect from '~/hooks/useSearchConsoleConnectRedirect';

/**
 * Clicking on the button to connect the Search Console account.
 *
 * @event gla_search_console_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the not-connected and authorizing sub-states of the Search Console connect flow.
 *
 * The auth-prompt-skip behavior (when the merchant already has a Merchant Center
 * connection) is rendered purely from the backend-supplied `skip_auth_prompt` flag on the
 * account payload — it is never re-derived on the client.
 *
 * @fires gla_search_console_account_connect_button_click
 */
const ConnectSearchConsole = () => {
	const { searchConsoleAccount } = useSearchConsoleAccount();

	const { onClick: handleConnectClick, loading } =
		useSearchConsoleConnectRedirect(
			__(
				'Unable to connect your Search Console account. Please try again later.',
				'google-listings-and-ads'
			),
			{ next_page_name: 'setup-search-console' }
		);

	const skipAuthPrompt = Boolean( searchConsoleAccount?.skip_auth_prompt );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={
				skipAuthPrompt
					? __(
							'Connect your Search Console property to track organic performance.',
							'google-listings-and-ads'
					  )
					: __(
							"Sign in to Google to connect your store's Search Console property and track organic performance.",
							'google-listings-and-ads'
					  )
			}
			indicator={
				<AppButton
					isSecondary
					loading={ loading }
					eventName="gla_search_console_account_connect_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectSearchConsole;

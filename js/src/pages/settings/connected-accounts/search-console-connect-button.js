/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useSearchConsoleConnectRedirect from '~/hooks/useSearchConsoleConnectRedirect';

/**
 * Clicking on the button to connect the Search Console account.
 *
 * @event gla_search_console_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the "Connect" button that starts the Search Console connect flow.
 *
 * The auth-prompt-skip behavior (when the merchant already has a Merchant
 * Center connection) is handled entirely by the backend redirect target —
 * this button only requests the connect URL and follows it.
 *
 * @fires gla_search_console_account_connect_button_click
 *
 * @return {JSX.Element} The connect button.
 */
export default function SearchConsoleConnectButton() {
	const { onClick: handleConnectClick, loading } =
		useSearchConsoleConnectRedirect(
			__(
				'Unable to connect your Search Console account. Please try again later.',
				'google-listings-and-ads'
			),
			{ next_page_name: 'setup-search-console' }
		);

	return (
		<AppButton
			isSecondary
			loading={ loading }
			eventName="gla_search_console_account_connect_button_click"
			eventProps={ { context: 'settings-search-console' } }
			onClick={ handleConnectClick }
		>
			{ __( 'Connect', 'google-listings-and-ads' ) }
		</AppButton>
	);
}

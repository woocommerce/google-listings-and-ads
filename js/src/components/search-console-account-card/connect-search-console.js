/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';

/**
 * Clicking on the button to connect the Search Console account.
 *
 * @event gla_search_console_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the not-connected and authorizing sub-states of the Search Console connect flow.
 *
 * The auth-prompt-skip behavior (AC-024, when the merchant already has a Merchant Center
 * connection) is rendered purely from the backend-supplied `skip_auth_prompt` flag on the
 * account payload — it is never re-derived on the client.
 *
 * @fires gla_search_console_account_connect_button_click
 */
const ConnectSearchConsole = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { searchConsoleAccount } = useSearchConsoleAccount();

	const query = { next_page_name: 'setup-search-console' };
	const path = addQueryArgs(
		`${ API_NAMESPACE }/search-console/connect`,
		query
	);
	const [ fetchSearchConsoleConnect, { loading, data } ] =
		useApiFetchCallback( { path } );

	const handleConnectClick = async () => {
		try {
			const d = await fetchSearchConsoleConnect();
			window.location.href = d.url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Search Console account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

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
					loading={ loading || data }
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

/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import LoadingLabel from '~/components/loading-label';
import useGoogleSearchConsoleConnectRedirect from './hooks/useGoogleSearchConsoleConnectRedirect';
import useSearchConsoleSetupCompleteCallback from './hooks/useSearchConsoleSetupCompleteCallback';
import {
	GOOGLE_SEARCH_CONSOLE_DESCRIPTION,
	SEARCH_CONSOLE_EVENT_CONTEXT,
} from './constants';

/**
 * Clicking on the button to connect the Google Search Console account.
 *
 * @event gla_google_search_console_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the not-connected Google Search Console account card.
 *
 * The Google auth-prompt-skip behavior (when the merchant already has a Merchant Center
 * connection) is handled entirely by the backend redirect target — this card only requests the
 * connect URL and follows it.
 *
 * On a confirmed return from that flow (`google-mc=connected` on the URL), confirms the OAuth
 * setup with the backend instead, showing a "Connecting…" indicator in place of the Connect
 * button while that request is in flight, then strips `google-mc` off the URL so a refresh
 * doesn't re-trigger the confirmation.
 *
 * @fires gla_google_search_console_account_connect_button_click
 *
 * @return {JSX.Element} The account card.
 */
const ConnectGoogleSearchConsoleAccountCard = () => {
	const isSearchConsoleOAuthReturn =
		getQuery()?.[ 'google-mc' ] === 'connected';
	const { connect: handleConnectClick, loading } =
		useGoogleSearchConsoleConnectRedirect();
	const [ handleCompleteSetup ] = useSearchConsoleSetupCompleteCallback();

	useEffect( () => {
		async function completeSetup() {
			await handleCompleteSetup();
			getHistory().replace( getNewPath( { 'google-mc': undefined } ) );
		}

		if ( isSearchConsoleOAuthReturn ) {
			completeSetup();
		}
	}, [ isSearchConsoleOAuthReturn, handleCompleteSetup ] );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ GOOGLE_SEARCH_CONSOLE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				isSearchConsoleOAuthReturn ? (
					<LoadingLabel
						text={ __( 'Connecting…', 'google-listings-and-ads' ) }
					/>
				) : (
					<AppButton
						eventName="gla_google_search_console_account_connect_button_click"
						eventProps={ { context: SEARCH_CONSOLE_EVENT_CONTEXT } }
						onClick={ handleConnectClick }
						loading={ loading }
						isSecondary
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
				)
			}
		/>
	);
};

export default ConnectGoogleSearchConsoleAccountCard;

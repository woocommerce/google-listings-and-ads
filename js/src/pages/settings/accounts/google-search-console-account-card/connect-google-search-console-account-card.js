/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import LoadingLabel from '~/components/loading-label';
import useGoogleSearchConsoleConnectRedirect from './hooks/useGoogleSearchConsoleConnectRedirect';
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
 * Renders the Google Search Console account card.
 *
 * @fires gla_google_search_console_account_connect_button_click
 *
 * @param {Object} props Component props.
 * @param {boolean} [props.isCompletingSetup] Whether a confirmed OAuth return is being completed, replacing the Connect button with a loading label.
 * @return {JSX.Element} The account card.
 */
const ConnectGoogleSearchConsoleAccountCard = ( {
	isCompletingSetup = false,
} ) => {
	const { connect: handleConnectClick, loading } =
		useGoogleSearchConsoleConnectRedirect();

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ GOOGLE_SEARCH_CONSOLE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				isCompletingSetup ? (
					<LoadingLabel
						text={ __( 'Connecting…', 'google-listings-and-ads' ) }
					/>
				) : (
					<AppButton
						eventName="gla_google_search_console_account_connect_button_click"
						eventProps={ {
							context: SEARCH_CONSOLE_EVENT_CONTEXT,
						} }
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

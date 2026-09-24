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
import { GOOGLE_SERVICE_OAUTH_PARAM, GOOGLE_SERVICE } from '~/constants';
import useScrollIntoView from '~/hooks/useScrollIntoView';
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
 * Renders the Google Search Console account card.
 *
 * @fires gla_google_search_console_account_connect_button_click
 *
 * @return {JSX.Element} The account card.
 */
const ConnectGoogleSearchConsoleAccountCard = () => {
	const { connect: handleConnectClick, loading } =
		useGoogleSearchConsoleConnectRedirect();
	const [ handleCompleteSetup ] = useSearchConsoleSetupCompleteCallback();
	const { containerRef, scrollIntoView } = useScrollIntoView();

	const query = getQuery();
	const isSearchConsoleOAuthReturn =
		query?.[ 'google-mc' ] === 'connected' &&
		query?.[ GOOGLE_SERVICE_OAUTH_PARAM ] === GOOGLE_SERVICE.SEARCH_CONSOLE;

	useEffect( () => {
		async function completeSetup() {
			scrollIntoView();

			await handleCompleteSetup();
			getHistory().replace(
				getNewPath( {
					'google-mc': undefined,
					[ GOOGLE_SERVICE_OAUTH_PARAM ]: undefined,
				} )
			);
		}

		if ( isSearchConsoleOAuthReturn ) {
			completeSetup();
		}
	}, [ isSearchConsoleOAuthReturn, handleCompleteSetup, scrollIntoView ] );

	return (
		<div ref={ containerRef }>
			<AccountCard
				appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
				description={ GOOGLE_SEARCH_CONSOLE_DESCRIPTION }
				alignIcon="top"
				alignIndicator="top"
				indicator={
					isSearchConsoleOAuthReturn ? (
						<LoadingLabel
							text={ __(
								'Connecting…',
								'google-listings-and-ads'
							) }
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
		</div>
	);
};

export default ConnectGoogleSearchConsoleAccountCard;

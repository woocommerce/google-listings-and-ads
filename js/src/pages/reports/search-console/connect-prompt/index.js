/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useGoogleSearchConsoleConnectRedirect from '~/google-search-console/hooks/useGoogleSearchConsoleConnectRedirect';
import { getAccountsSettingsUrl } from '~/utils/urls';
import {
	FOCUS_ACCOUNT_CARD_PARAM,
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS,
	GOOGLE_SERVICE,
} from '~/constants';
import googleSearchConsoleLogoURL from '~/images/logo/google-search-console-logo.svg';
import './connect-prompt.scss';

const { INCOMPLETE, ACTION_NEEDED, RECONNECT, CONNECTION_FAILED } =
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

// Statuses of an existing connection that the merchant has to fix in Settings.
const NEEDS_ATTENTION_STATUSES = [
	INCOMPLETE,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
];

const CONNECT_COPY = {
	heading: __(
		'Connect your Search Console profile',
		'google-listings-and-ads'
	),
	body: __(
		'Connect it to see how shoppers find your store in organic Google Search, including clicks, impressions, and top queries.',
		'google-listings-and-ads'
	),
};

const NEEDS_ATTENTION_COPY = {
	heading: __(
		'Your Search Console profile needs attention',
		'google-listings-and-ads'
	),
	body: __(
		'Go to settings to finish connecting Search Console and see how shoppers find your store in organic Google Search.',
		'google-listings-and-ads'
	),
};

const searchConsoleSettingsUrl = getAccountsSettingsUrl( {
	[ FOCUS_ACCOUNT_CARD_PARAM ]: GOOGLE_SERVICE.SEARCH_CONSOLE,
} );

/**
 * Prompt shown on the Organic search tab in place of report data while Search Console isn't
 * connected. It asks to connect Search Console, or to fix an existing connection in Settings.
 *
 * @return {JSX.Element} The prompt.
 *
 * @fires gla_google_search_console_account_connect_button_click with `{ context: 'reports-search-console' }`.
 */
const ConnectPrompt = () => {
	const { account } = useGoogleSearchConsoleAccount();
	const { connect: handleConnectClick, loading } =
		useGoogleSearchConsoleConnectRedirect();
	const needsAttention = NEEDS_ATTENTION_STATUSES.includes( account?.status );
	const { heading, body } = needsAttention
		? NEEDS_ATTENTION_COPY
		: CONNECT_COPY;

	return (
		<Flex
			className="gla-search-console-connect-prompt"
			direction="column"
			align="center"
			gap={ 3 }
		>
			<img
				className="gla-search-console-connect-prompt__logo"
				src={ googleSearchConsoleLogoURL }
				alt={ __(
					'Google Search Console logo',
					'google-listings-and-ads'
				) }
				width="56"
				height="56"
			/>
			<h2 className="gla-search-console-connect-prompt__heading">
				{ heading }
			</h2>
			<p className="gla-search-console-connect-prompt__body">{ body }</p>
			{ needsAttention ? (
				<AppButton href={ searchConsoleSettingsUrl } isSecondary>
					{ __( 'Go to settings', 'google-listings-and-ads' ) }
				</AppButton>
			) : (
				<AppButton
					eventName="gla_google_search_console_account_connect_button_click"
					eventProps={ { context: 'reports-search-console' } }
					onClick={ handleConnectClick }
					loading={ loading }
					isSecondary
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			) }
		</Flex>
	);
};

export default ConnectPrompt;

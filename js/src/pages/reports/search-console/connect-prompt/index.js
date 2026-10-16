/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, warning } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useGoogleSearchConsoleConnectRedirect from '~/hooks/useGoogleSearchConsoleConnectRedirect';
import { getAccountsSettingsUrl } from '~/utils/urls';
import { FOCUS_ACCOUNT_CARD_PARAM, GOOGLE_SERVICE } from '~/constants';
import googleSearchConsoleLogoURL from '~/images/logo/google-search-console-logo.svg';
import './connect-prompt.scss';

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
 * connected.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.needsAttention=false] Whether an existing connection needs fixing in
 *   Settings, rather than Search Console never having been connected.
 * @return {JSX.Element} The prompt.
 *
 * @fires gla_google_search_console_account_connect_button_click with `{ context: 'reports-search-console' }`.
 */
const ConnectPrompt = ( { needsAttention = false } ) => {
	const { connect: handleConnectClick, loading } =
		useGoogleSearchConsoleConnectRedirect();
	const { heading, body } = needsAttention
		? NEEDS_ATTENTION_COPY
		: CONNECT_COPY;

	return (
		<div className="gla-search-console-connect-prompt">
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
				{ needsAttention && <Icon icon={ warning } /> }
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
		</div>
	);
};

export default ConnectPrompt;

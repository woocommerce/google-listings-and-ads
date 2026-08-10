/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useSearchConsoleConnectRedirect from '~/hooks/useSearchConsoleConnectRedirect';
import './error-card.scss';

/**
 * Clicking on the button to reconnect the Search Console account after the connection expired.
 *
 * @event gla_search_console_reconnect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the "Reconnect" state — shown when the Search Console connection has expired. Modeled on `RequestFullAccessGoogleAccountCard`'s error-styled description +
 * destructive button template.
 *
 * @fires gla_search_console_reconnect_button_click
 */
const ReconnectCard = () => {
	const { onClick: handleClick, loading } = useSearchConsoleConnectRedirect(
		__(
			'Unable to reconnect your Search Console account. Please try again later.',
			'google-listings-and-ads'
		)
	);

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			alignIcon="top"
			description={
				<p>
					<em>
						{ createInterpolateElement(
							__(
								'<alert>Connection expired:</alert> Your Search Console connection needs to be re-authorized.',
								'google-listings-and-ads'
							),
							{
								alert: (
									<span className="gla-search-console-account-card__error-text" />
								),
							}
						) }
					</em>
				</p>
			}
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					isDestructive
					loading={ loading }
					eventName="gla_search_console_reconnect_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
				>
					{ __( 'Reconnect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ReconnectCard;

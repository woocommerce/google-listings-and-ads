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
 * Clicking on the button to retry connecting the Search Console account after the initial
 * attempt failed.
 *
 * @event gla_search_console_connection_failed_retry_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the "Connection failed" state — shown when the initial Search Console connection
 * attempt failed, with a retry action. Modeled on `RequestFullAccessGoogleAccountCard`'s
 * error-styled description + destructive button template.
 *
 * @fires gla_search_console_connection_failed_retry_button_click
 */
const ConnectionFailedCard = () => {
	const { onClick: handleClick, loading } = useSearchConsoleConnectRedirect(
		__(
			'Unable to connect your Search Console account. Please try again later.',
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
								"<alert>Connection failed:</alert> We couldn't connect your Search Console account. Please try again.",
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
					eventName="gla_search_console_connection_failed_retry_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
				>
					{ __( 'Retry', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectionFailedCard;

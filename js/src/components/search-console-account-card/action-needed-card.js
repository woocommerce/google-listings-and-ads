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
import useVerifySearchConsoleProperty from '~/hooks/useVerifySearchConsoleProperty';
import './error-card.scss';

/**
 * Clicking on the button to re-verify the Search Console property after verification was lost.
 *
 * @event gla_search_console_action_needed_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the "Action needed" state — shown when Search Console verification is found to have
 * been lost outside of the connect flow. Modeled on `RequestFullAccessGoogleAccountCard`'s
 * error-styled description + destructive button template.
 *
 * @fires gla_search_console_action_needed_button_click
 */
const ActionNeededCard = () => {
	const { onClick: handleClick, loading } = useVerifySearchConsoleProperty();

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			alignIcon="top"
			description={
				<p>
					<em>
						{ createInterpolateElement(
							__(
								'<alert>Action needed:</alert> Your Search Console property is no longer verified. Verify it again to keep tracking organic performance.',
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
					eventName="gla_search_console_action_needed_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ActionNeededCard;

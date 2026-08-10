/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useSearchConsoleConnectRedirect from '~/hooks/useSearchConsoleConnectRedirect';

/**
 * Clicking on the button to resume an abandoned Search Console connect flow.
 *
 * @event gla_search_console_resume_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the generic "incomplete" fallback state — shown when the connect flow was abandoned
 * partway through in a way that isn't covered by the more specific property-selection or
 * verification steps. Always shows a clear resume path and is never rendered as a silent
 * success.
 *
 * @fires gla_search_console_resume_button_click
 */
const IncompleteResumeCard = () => {
	const { onClick: handleClick, loading } = useSearchConsoleConnectRedirect(
		__(
			'Unable to resume your Search Console connection. Please try again later.',
			'google-listings-and-ads'
		)
	);

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ __(
				"Your Search Console connection isn't complete yet.",
				'google-listings-and-ads'
			) }
			indicator={
				<AppButton
					isSecondary
					loading={ loading }
					eventName="gla_search_console_resume_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
				>
					{ __( 'Resume setup', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default IncompleteResumeCard;

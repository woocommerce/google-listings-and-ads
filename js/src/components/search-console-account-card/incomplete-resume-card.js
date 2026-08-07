/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

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
 * success (AC-018).
 *
 * @fires gla_search_console_resume_button_click
 */
const IncompleteResumeCard = () => {
	const { createNotice } = useDispatchCoreNotices();

	const [ fetchSearchConsoleConnect, { loading, data } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/search-console/connect`,
		} );

	const handleClick = async () => {
		try {
			const d = await fetchSearchConsoleConnect();
			window.location.href = d.url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to resume your Search Console connection. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

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
					loading={ loading || data }
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

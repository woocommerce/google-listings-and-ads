/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useVerifyGoogleSearchConsoleProperty from '../../hooks/useVerifyGoogleSearchConsoleProperty';
import NoticeDetail from '../notice-detail';

/**
 * Clicking on the button to verify the Google Search Console property, either during the normal
 * verification step or after re-verification is needed following the "action needed" state.
 *
 * @event gla_google_search_console_verify_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the "action needed" step's detail, shown when Google Search Console verification is found to
 * have been lost outside of the connect flow.
 *
 * @fires gla_google_search_console_verify_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function ActionNeeded() {
	const { verify: handleClick, loading } =
		useVerifyGoogleSearchConsoleProperty();

	return (
		<NoticeDetail
			status="warning"
			title={ __(
				'Your Google Search Console property is no longer verified',
				'google-listings-and-ads'
			) }
			body={ __(
				'Verify it again to keep tracking organic performance.',
				'google-listings-and-ads'
			) }
			actions={ [
				<AppButton
					key="verify"
					eventName="gla_google_search_console_verify_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleClick }
					loading={ loading }
					isSecondary
				>
					{ __( 'Verify site', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}

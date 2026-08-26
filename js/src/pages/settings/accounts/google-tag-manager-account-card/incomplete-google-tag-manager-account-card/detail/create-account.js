/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useRefreshGoogleTagManagerConnection from '../../hooks/useRefreshGoogleTagManagerConnection';
import NoticeDetail from '../notice-detail';
import CreateNewAccountLink from '../create-new-account-link';

/**
 * Clicking on the button to re-check for a newly created Google Tag Manager account.
 *
 * @event gla_google_tag_manager_check_connection_again_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the zero-accounts detail: a "Create new account" link to Google's own GTM
 * account-creation flow (the GTM API has no account-creation endpoint), plus a "Check again"
 * button that re-fetches the merchant's accounts without a full page reload.
 *
 * @fires gla_google_tag_manager_check_connection_again_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function CreateAccount() {
	const { refresh, isResolving } = useRefreshGoogleTagManagerConnection();
	const { google } = useGoogleAccount();
	const email = google?.email || __( 'Google', 'google-listings-and-ads' );

	return (
		<NoticeDetail
			status="warning"
			body={ sprintf(
				/* translators: %s: the connected Google account's email address, or "Google" if not yet known. */
				__(
					"We couldn't find a Google Tag Manager account associated with your %s account. If you have already created an account, click the 'Check again' button to fetch your account details.",
					'google-listings-and-ads'
				),
				email
			) }
			actions={ [
				<CreateNewAccountLink key="create-account" />,
				<AppButton
					key="check-again"
					onClick={ refresh }
					eventName="gla_google_tag_manager_check_connection_again_button_click"
					eventProps={ { context: 'settings-tag-manager' } }
					disabled={ isResolving }
					loading={ isResolving }
					isTertiary
				>
					{ __( 'Check again', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}

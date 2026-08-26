/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getTagManagerCreateAccountUrl } from '~/utils/urls';
import AppButton from '~/components/app-button';
import useRefreshGoogleTagManagerConnection from '../../hooks/useRefreshGoogleTagManagerConnection';
import NoticeDetail from '../notice-detail';

/**
 * Clicking on the button to create a new Google Tag Manager account off-site.
 *
 * @event gla_google_tag_manager_create_account_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Clicking on the button to re-check for a newly created Google Tag Manager account.
 *
 * @event gla_google_tag_manager_check_connection_again_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the zero-accounts detail: a "Create account" CTA linking to Google's own GTM
 * account-creation flow (the GTM API has no account-creation endpoint), plus a "Check again"
 * button that re-fetches the merchant's accounts without a full page reload.
 *
 * @fires gla_google_tag_manager_create_account_button_click
 * @fires gla_google_tag_manager_check_connection_again_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function CreateAccount() {
	const { refresh, isResolving } = useRefreshGoogleTagManagerConnection();

	return (
		<NoticeDetail
			status="warning"
			title={ __(
				'No Google Tag Manager account found',
				'google-listings-and-ads'
			) }
			body={ __(
				'We couldn’t find a Google Tag Manager account associated with your Google account. If you have already created an account, click the “Check again” button to fetch your account details.',
				'google-listings-and-ads'
			) }
			actions={ [
				<AppButton
					key="create-account"
					href={ getTagManagerCreateAccountUrl() }
					target="_blank"
					rel="noreferrer"
					eventName="gla_google_tag_manager_create_account_button_click"
					eventProps={ { context: 'settings-tag-manager' } }
					isSecondary
				>
					{ __( 'Create new account', 'google-listings-and-ads' ) }
				</AppButton>,
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

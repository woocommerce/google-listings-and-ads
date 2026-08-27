/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import useGoogleAccount from '~/hooks/useGoogleAccount';
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
 * button that re-fetches the connection and the accounts list — only these two resolvers, so
 * this doesn't touch the containers list or any other card. `isRefreshing` is local state
 * tracking that in-flight refetch.
 *
 * @fires gla_google_tag_manager_check_connection_again_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function CreateAccount() {
	const {
		fetchGoogleTagManagerAccount,
		fetchExistingGoogleTagManagerAccounts,
	} = useAppDispatch();
	const { google } = useGoogleAccount();
	const [ isRefreshing, setIsRefreshing ] = useState( false );

	// `Accounts` already gates rendering on every account — including Google's — having
	// resolved, so `google` itself is guaranteed set here; only its `email` can still be empty.
	const email = google.email || __( 'Google', 'google-listings-and-ads' );

	const handleCheckAgainClick = async () => {
		setIsRefreshing( true );
		await Promise.all( [
			fetchGoogleTagManagerAccount(),
			fetchExistingGoogleTagManagerAccounts(),
		] );
		setIsRefreshing( false );
	};

	return (
		<NoticeDetail
			status="warning"
			body={ sprintf(
				/* translators: %s: the connected Google account's email address, or "Google" if not set. */
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
					onClick={ handleCheckAgainClick }
					eventName="gla_google_tag_manager_check_connection_again_button_click"
					eventProps={ { context: 'settings-tag-manager' } }
					disabled={ isRefreshing }
					loading={ isRefreshing }
					isTertiary
				>
					{ __( 'Check again', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		/>
	);
}

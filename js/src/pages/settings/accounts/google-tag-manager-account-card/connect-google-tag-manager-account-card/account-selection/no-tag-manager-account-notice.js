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
import NoticeDetail from './notice-detail';
import CreateNewAccountLink from './create-new-account-link';

/**
 * Clicking on the button to re-check for a newly created Google Tag Manager account.
 *
 * @event gla_google_tag_manager_check_connection_again_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-tag-manager'.
 */

/**
 * Renders the zero-accounts notice: explains no Google Tag Manager account was found for the
 * connected Google account, and offers a "Check again" refetch plus a create-new-account link.
 *
 * @fires gla_google_tag_manager_check_connection_again_button_click
 *
 * @return {JSX.Element} The notice.
 */
export default function NoTagManagerAccountNotice() {
	const {
		fetchGoogleTagManagerAccount,
		fetchExistingGoogleTagManagerAccounts,
	} = useAppDispatch();
	const { google } = useGoogleAccount();
	const [ isRefreshing, setIsRefreshing ] = useState( false );

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
			body={
				<p>
					{ sprintf(
						/* translators: %s: the connected Google account's email address, or "Google" if not set. */
						__(
							"We couldn't find a Google Tag Manager account associated with your %s account. If you have already created an account, click the 'Check again' button to fetch your account details.",
							'google-listings-and-ads'
						),
						email
					) }
				</p>
			}
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

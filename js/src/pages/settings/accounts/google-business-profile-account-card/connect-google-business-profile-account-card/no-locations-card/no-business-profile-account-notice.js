/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import { GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT } from '../../constants';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import NoticeDetail from '../../notice-detail';
import CreateNewLink from '../create-new-link';
import CreateNewRefreshNotice from '../create-new-refresh-notice';

/**
 * Clicking "Check again" to look for a Google Business Profile again after none was found.
 *
 * @event gla_google_business_profile_check_again_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-business-profile'.
 */

/**
 * Renders the zero-locations notice: explains no Google Business Profile was found for the
 * connected Google account, and offers a "Check again" refetch plus a create-new-account link. Once
 * that link is clicked, the refresh reminder shows below the notice.
 *
 * @fires gla_google_business_profile_check_again_button_click with `{ context: 'settings-business-profile' }`.
 *
 * @return {JSX.Element} The notice, and the refresh reminder once the link is clicked.
 */
export default function NoBusinessProfileAccountNotice() {
	const {
		fetchGoogleBusinessProfileAccount,
		fetchGoogleBusinessProfileLocations,
	} = useAppDispatch();
	const { google } = useGoogleAccount();
	const [ isRefreshing, setIsRefreshing ] = useState( false );
	const [ hasClickedCreate, setHasClickedCreate ] = useState( false );

	const email = google?.email || __( 'Google', 'google-listings-and-ads' );

	const handleCheckAgainClick = async () => {
		setIsRefreshing( true );
		await Promise.all( [
			fetchGoogleBusinessProfileAccount(),
			fetchGoogleBusinessProfileLocations(),
		] );
		setIsRefreshing( false );
	};

	const handleCreateClick = () => {
		setHasClickedCreate( true );
	};

	return (
		<Flex direction="column" gap={ 4 }>
			<NoticeDetail
				status="warning"
				body={
					<p>
						{ sprintf(
							/* translators: %s: the connected Google account's email address, or "Google" if not set. */
							__(
								"We couldn't find a Google Business Profile associated with your %s account. If you have already created an account, click the 'Check again' button to fetch your account details.",
								'google-listings-and-ads'
							),
							email
						) }
					</p>
				}
				actions={ [
					<AppButton
						key="check-again"
						eventName="gla_google_business_profile_check_again_button_click"
						eventProps={ {
							context: GOOGLE_BUSINESS_PROFILE_SETTINGS_CONTEXT,
						} }
						onClick={ handleCheckAgainClick }
						disabled={ isRefreshing }
						loading={ isRefreshing }
						isSecondary
					>
						{ __( 'Check again', 'google-listings-and-ads' ) }
					</AppButton>,
					<CreateNewLink
						key="create-account"
						type="account"
						onClick={ handleCreateClick }
					/>,
				] }
			/>
			{ hasClickedCreate && <CreateNewRefreshNotice type="account" /> }
		</Flex>
	);
}

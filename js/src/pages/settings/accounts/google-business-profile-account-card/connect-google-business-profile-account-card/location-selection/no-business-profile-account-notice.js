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
import NoticeDetail from '../../notice-detail';
import CreateNewAccountLink from './create-new-account-link';

/**
 * Renders the zero-locations notice: explains no Google Business Profile was found for the
 * connected Google account, and offers a "Check again" refetch plus a create-new-account link.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onCreateAccountClick Callback when the user clicks the create-new-account link.
 * @return {JSX.Element} The notice.
 */
export default function NoBusinessProfileAccountNotice( {
	onCreateAccountClick,
} ) {
	const {
		fetchGoogleBusinessProfileAccount,
		fetchGoogleBusinessProfileLocations,
	} = useAppDispatch();
	const { google } = useGoogleAccount();
	const [ isRefreshing, setIsRefreshing ] = useState( false );

	const email = google?.email || __( 'Google', 'google-listings-and-ads' );

	const handleCheckAgainClick = async () => {
		setIsRefreshing( true );
		await Promise.all( [
			fetchGoogleBusinessProfileAccount(),
			fetchGoogleBusinessProfileLocations(),
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
					onClick={ handleCheckAgainClick }
					disabled={ isRefreshing }
					loading={ isRefreshing }
					isSecondary
				>
					{ __( 'Check again', 'google-listings-and-ads' ) }
				</AppButton>,
				<CreateNewAccountLink
					key="create-account"
					onClick={ onCreateAccountClick }
				/>,
			] }
		/>
	);
}

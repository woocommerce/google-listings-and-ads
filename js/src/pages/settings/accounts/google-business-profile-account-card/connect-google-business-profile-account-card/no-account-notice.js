/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleAccount from '~/hooks/useGoogleAccount';
import AppButton from '~/components/app-button';
import NoticeDetail from '../notice-detail';
import CreateLink from './create-link';

/**
 * Renders the notice shown when the connected Google Account has no Google Business Profile:
 * a "Create new account" link to Google and a "Check again" action. Once the merchant has clicked
 * through to Google, the notice asks them to refresh the page instead.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onCheckAgain Called when the merchant asks to look for profiles again.
 * @return {JSX.Element} The notice.
 */
export default function NoAccountNotice( { onCheckAgain } ) {
	const { google } = useGoogleAccount();
	const email = google?.email || __( 'Google', 'google-listings-and-ads' );
	const [ hasClickedCreate, setHasClickedCreate ] = useState( false );

	const handleCreateClick = () => {
		setHasClickedCreate( true );
	};

	const body = hasClickedCreate
		? __(
				'Refresh the page to see your new account.',
				'google-listings-and-ads'
		  )
		: sprintf(
				/* translators: %s: the connected Google Account's email address, or "Google" if not known. */
				__(
					"We couldn't find a Google Business Profile associated with your %s account. If you have already created an account, click the 'Check again' button to fetch your account details.",
					'google-listings-and-ads'
				),
				email
		  );

	return (
		<NoticeDetail
			status={ hasClickedCreate ? 'info' : 'warning' }
			body={ <p>{ body }</p> }
			actions={ [
				<AppButton
					key="check-again"
					isSecondary
					onClick={ onCheckAgain }
				>
					{ __( 'Check again', 'google-listings-and-ads' ) }
				</AppButton>,
				<CreateLink
					key="create"
					type="account"
					onClick={ handleCreateClick }
				/>,
			] }
		/>
	);
}

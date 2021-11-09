/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import CreateAccount from './create-account';
import CreateAccountButton from './create-account-button';

const pageName = 'setup-ads';

export default function AuthorizeAds( { additionalScopeEmail } ) {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, { loading, data } ] = useGoogleAuthorization(
		pageName,
		additionalScopeEmail
	);

	const handleBeforeAskTerms = () => {
		fetchGoogleConnect()
			.then( ( { url } ) => {
				window.location.href = url;
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'Unable to get Google authorization page. Please try again later.',
						'google-listings-and-ads'
					)
				);
			} );

		// Do not continue to ask for terms agreement when requesting needed permission.
		return false;
	};

	return (
		<CreateAccount
			button={
				<CreateAccountButton
					loading={ loading || data }
					onBeforeAskTerms={ handleBeforeAskTerms }
					eventName="gla_google_account_connect_button_click"
					eventProps={ { context: pageName, action: 'scope' } }
				/>
			}
		/>
	);
}

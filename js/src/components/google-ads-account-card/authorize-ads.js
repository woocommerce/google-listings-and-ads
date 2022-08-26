/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import { glaData } from '.~/constants';

/**
 * @param {Object} props React props
 * @param {string} props.additionalScopeEmail
 * @fires gla_google_account_connect_button_click with `{ action: 'scope', context: 'setup-ads' }`
 */
const AuthorizeAds = ( { additionalScopeEmail } ) => {
	const nextPageName = glaData.mcSetupComplete ? 'setup-ads' : 'setup-mc';
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, { loading, data } ] = useGoogleAuthorization(
		nextPageName,
		additionalScopeEmail
	);

	const handleClick = () => {
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
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			indicator={
				<AppButton
					isSecondary
					loading={ loading || data }
					onClick={ handleClick }
					text={ __(
						'Allow full access',
						'google-listings-and-ads'
					) }
					eventName="gla_google_account_connect_button_click"
					eventProps={ { context: 'setup-ads', action: 'scope' } }
				/>
			}
		/>
	);
};

export default AuthorizeAds;

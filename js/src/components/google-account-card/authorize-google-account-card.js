/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import readMoreLink from './read-more-link';
import './authorize-google-account-card.scss';

/**
 * Renders an AccountCard based on Google appearance for requesting Google authorization from user.
 *
 * @param {Object} props React props.
 * @param {string} [props.additionalScopeEmail] Specify the email to be requested additional scopes. Set this prop only if wants to request a partial oauth to Google.
 */
export default function AuthorizeGoogleAccountCard( { additionalScopeEmail } ) {
	const pageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const isAskingScope = Boolean( additionalScopeEmail );
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, { loading, data } ] = useGoogleAuthorization(
		pageName,
		additionalScopeEmail
	);

	const handleConnectClick = async () => {
		try {
			const { url } = await fetchGoogleConnect();
			window.location.href = url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Google account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			alignIcon="top"
			description={
				<>
					{ additionalScopeEmail }
					<p>
						<em>
							{ createInterpolateElement(
								__(
									'<alert>Error:</alert> You did not allow WooCommerce sufficient access to your Google account. You must select all checkboxes in the Google account modal to proceed. <link>Read more</link>',
									'google-listings-and-ads'
								),
								{
									alert: (
										<span className="gla-authorize-google-account-card__error-text" />
									),
									link: readMoreLink,
								}
							) }
						</em>
					</p>
				</>
			}
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					isDestructive={ isAskingScope }
					loading={ loading || data }
					eventName="gla_google_account_connect_button_click"
					text={ __(
						'Allow full access',
						'google-listings-and-ads'
					) }
					onClick={ handleConnectClick }
				/>
			}
		/>
	);
}

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
import readMoreLink from './read-more-link';
import useGoogleConnectFlow from './use-google-connect-flow';
import './request-full-access-google-account-card.scss';

/**
 * Renders an AccountCard based on Google appearance for requesting full access permission from user.
 *
 * @param {Object} props React props.
 * @param {string} props.additionalScopeEmail Specify the email to be requested additional permission scopes to Google.
 */
export default function RequestFullAccessGoogleAccountCard( {
	additionalScopeEmail,
} ) {
	const pageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const [ handleConnect, { loading, data } ] = useGoogleConnectFlow(
		pageName,
		additionalScopeEmail
	);

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
									'<alert>Uh-oh!</alert> You did not allow WooCommerce sufficient access to your Google account. You must allow all required permissions in the Google authorization page to proceed. <link>Read more</link>',
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
					isDestructive
					loading={ loading || data }
					eventName="gla_google_account_connect_button_click"
					eventProps={ {
						context: pageName,
						action: 'scope',
					} }
					text={ __(
						'Allow full access',
						'google-listings-and-ads'
					) }
					onClick={ handleConnect }
				/>
			}
		/>
	);
}

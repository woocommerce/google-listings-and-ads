/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
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
	const [ handleConnect, { loading, data } ] = useGoogleConnectFlow(
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
					isDestructive
					loading={ loading || data }
					eventName="gla_google_account_connect_button_click"
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

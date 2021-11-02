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
import AppDocumentationLink from '.~/components/app-documentation-link';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import './authorize-google-account-card.scss';

const readMoreLink = (
	<AppDocumentationLink
		context="setup-mc-accounts"
		linkId="required-google-permissions"
		href="https://docs.woocommerce.com/document/google-listings-and-ads/#required-google-permissions"
	/>
);

/**
 * Renders an AccountCard based on Google appearance for requesting Google authorization from user.
 *
 * @param {Object} props React props.
 * @param {string} [props.additionalScopeEmail] Specify the email to be requested additional scopes. Set this prop only if wants to request a partial oauth to Google.
 * @param {boolean} [props.disabled=false] Whether display in disabled style and disable all controllable elements within this component.
 */
export default function AuthorizeGoogleAccountCard( {
	additionalScopeEmail,
	disabled = false,
} ) {
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

	const description = isAskingScope ? (
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
	) : (
		<>
			{ __(
				'Required to sync with Google Merchant Center and Google Ads.',
				'google-listings-and-ads'
			) }
			<p>
				<em>
					{ createInterpolateElement(
						__(
							'You will be prompted to give WooCommerce access to your Google account. Please check all the checkboxes to give WooCommerce all required permissions. <link>Read more</link>',
							'google-listings-and-ads'
						),
						{ link: readMoreLink }
					) }
				</em>
			</p>
		</>
	);

	const buttonText = isAskingScope
		? __( 'Allow full access', 'google-listings-and-ads' )
		: __( 'Connect', 'google-listings-and-ads' );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			disabled={ disabled }
			hideIcon
			alignIcon="top"
			description={ description }
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					isDestructive={ isAskingScope }
					disabled={ disabled }
					loading={ loading || data }
					eventName="gla_google_account_connect_button_click"
					text={ buttonText }
					onClick={ handleConnectClick }
				/>
			}
		/>
	);
}

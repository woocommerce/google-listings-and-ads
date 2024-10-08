/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import readMoreLink from '../google-account-card/read-more-link';
import useGoogleConnectFlow from '../google-account-card/use-google-connect-flow';
import AppDocumentationLink from '../app-documentation-link';

/**
 * @param {Object} props React props
 * @param {boolean} props.disabled
 * @fires gla_google_account_connect_button_click with `{ action: 'authorization', context: 'reconnect' }`
 * @fires gla_google_account_connect_button_click with `{ action: 'authorization', context: 'setup-mc' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'required-google-permissions', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/setup-and-configuration/#required-google-permissions' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'google-mc-terms-of-service', href: 'https://support.google.com/merchants/answer/160173' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-ads', link_id: 'google-ads-terms-of-service', href: 'https://support.google.com/adspolicy/answer/54818' }`
 */
const ConnectGoogleComboAccountCard = ( { disabled } ) => {
	const pageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const [ handleConnect, { loading, data } ] =
		useGoogleConnectFlow( pageName );
	const [ termsAccepted, setTermsAccepted ] = useState( false );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			className="gla-google-combo-account-card gla-google-combo-account-card--connect"
			disabled={ disabled }
			alignIcon="top"
			description={
				<>
					<p>
						{ __(
							'Required to sync with Google Merchant Center and Google Ads.',
							'google-listings-and-ads'
						) }
					</p>
					<CheckboxControl
						label={ createInterpolateElement(
							__(
								'I accept the terms and conditions of <linkMerchant>Merchant Center</linkMerchant> and <linkAds>Google Ads</linkAds>',
								'google-listings-and-ads'
							),
							{
								linkMerchant: (
									<AppDocumentationLink
										context="setup-mc-accounts"
										linkId="google-mc-terms-of-service"
										href="https://support.google.com/merchants/answer/160173"
									/>
								),
								linkAds: (
									<AppDocumentationLink
										context="setup-ads"
										linkId="google-ads-terms-of-service"
										href="https://support.google.com/adspolicy/answer/54818"
									/>
								),
							}
						) }
						checked={ termsAccepted }
						onChange={ setTermsAccepted }
						disabled={ disabled }
					/>
				</>
			}
			helper={ createInterpolateElement(
				__(
					'You will be prompted to give WooCommerce access to your Google account. Please check all the checkboxes to give WooCommerce all required permissions. <link>Read more</link>',
					'google-listings-and-ads'
				),
				{
					link: readMoreLink,
				}
			) }
			alignIndicator="top"
			indicator={
				<AppButton
					isSecondary
					disabled={ disabled || ! termsAccepted }
					loading={ loading || data }
					eventName="gla_google_account_connect_button_click"
					eventProps={ {
						context: pageName,
						action: 'authorization',
					} }
					text={ __( 'Connect', 'google-listings-and-ads' ) }
					onClick={ handleConnect }
				/>
			}
		/>
	);
};

export default ConnectGoogleComboAccountCard;

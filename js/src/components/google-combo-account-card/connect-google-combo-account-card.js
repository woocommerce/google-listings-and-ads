/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import {
	ReadMoreLink,
	useGoogleConnectFlow,
} from '~/components/google-account-card';
import AppDocumentationLink from '../app-documentation-link';
import { glaData } from '~/constants';

const linkAds = (
	<AppDocumentationLink
		context="setup-ads"
		href="https://support.google.com/adspolicy/answer/54818"
		linkId="google-ads-terms-of-service"
	/>
);

/**
 * Renders a card to connect to Google Account.
 *
 * Please note that this component is only used on the onboarding flow.
 *
 * @param {Object} props React props
 * @param {boolean} [props.disabled] Whether display the Card in disabled style.
 *
 * @fires gla_google_account_connect_button_click with `{ action: 'authorization', context: 'setup-mc' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'required-google-permissions', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/setup-and-configuration/#required-google-permissions' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'google-mc-terms-of-service', href: 'https://support.google.com/merchants/answer/160173' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-ads', link_id: 'google-ads-terms-of-service', href: 'https://support.google.com/adspolicy/answer/54818' }`
 */
const ConnectGoogleComboAccountCard = ( { disabled } ) => {
	const pageName = 'setup-mc';
	const [ handleConnect, { loading, data } ] =
		useGoogleConnectFlow( pageName );
	const [ termsAccepted, setTermsAccepted ] = useState( false );
	const { serviceBasedMerchant } = glaData;

	const cardContent = serviceBasedMerchant
		? {
				description: __(
					'Required to sync with Google Ads.',
					'google-listings-and-ads'
				),
				terms: __(
					'I accept the terms and conditions of <linkAds>Google Ads</linkAds>',
					'google-listings-and-ads'
				),
				components: { linkAds },
		  }
		: {
				description: __(
					'Required to sync with Google Merchant Center and Google Ads.',
					'google-listings-and-ads'
				),
				terms: __(
					'I accept the terms and conditions of <linkMerchant>Merchant Center</linkMerchant> and <linkAds>Google Ads</linkAds>',
					'google-listings-and-ads'
				),
				components: {
					linkAds,
					linkMerchant: (
						<AppDocumentationLink
							context="setup-mc-accounts"
							href="https://support.google.com/merchants/answer/160173"
							linkId="google-mc-terms-of-service"
						/>
					),
				},
		  };

	const termsLabel = createInterpolateElement(
		cardContent.terms,
		cardContent.components
	);

	return (
		<AccountCard
			alignIcon="top"
			alignIndicator="top"
			appearance={ APPEARANCE.GOOGLE }
			className="gla-google-combo-service-account-card--google"
			description={
				<>
					<p>{ cardContent.description }</p>
					<CheckboxControl
						checked={ termsAccepted }
						disabled={ disabled }
						label={ termsLabel }
						onChange={ setTermsAccepted }
					/>
				</>
			}
			disabled={ disabled }
			helper={ createInterpolateElement(
				__(
					'You will be prompted to give WooCommerce access to your Google account. Please check all the checkboxes to give WooCommerce all required permissions. <link>Read more</link>',
					'google-listings-and-ads'
				),
				{
					link: ReadMoreLink,
				}
			) }
			indicator={
				<AppButton
					disabled={ disabled || ! termsAccepted }
					eventName="gla_google_account_connect_button_click"
					eventProps={ {
						context: pageName,
						action: 'authorization',
					} }
					loading={ loading || data }
					onClick={ handleConnect }
					text={ __( 'Connect', 'google-listings-and-ads' ) }
					isSecondary
				/>
			}
		/>
	);
};

export default ConnectGoogleComboAccountCard;

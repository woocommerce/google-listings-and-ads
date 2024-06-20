/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import './index.scss';

/**
 * Clicking on the button to create a new Google Ads account, after agreeing to the terms and conditions.
 *
 * @event gla_ads_account_create_button_click
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * Terms and conditions agreement modal.
 *
 * @fires gla_ads_account_create_button_click When agreed by clicking "Create account".
 * @fires gla_documentation_link_click with `{ context: 'setup-ads', link_id: 'shopping-ads-policies', href: 'https://support.google.com/merchants/answer/6149970' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-ads', link_id: 'google-ads-terms-of-service', href: 'https://support.google.com/adspolicy/answer/54818' }`
 * @param {Object} props React props
 * @param {Function} [props.onCreateAccount] Callback function when account is created
 * @param {Function} [props.onRequestClose] Callback function when the modal is closed
 */
const TermsModal = ( {
	onCreateAccount = () => {},
	onRequestClose = () => {},
} ) => {
	const [ agree, setAgree ] = useState( false );
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );

	const handleCreateAccountClick = () => {
		onCreateAccount();
		onRequestClose();
	};

	return (
		<AppModal
			className="gla-ads-terms-modal"
			title={ __(
				'Create Google Ads Account',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="1"
					isPrimary
					disabled={ ! agree }
					eventName="gla_ads_account_create_button_click"
					eventProps={ getEventProps() }
					onClick={ handleCreateAccountClick }
				>
					{ __( 'Create account', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p className="main">
				{ __(
					'By creating a Google Ads account, you agree to the following terms and conditions:',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ createInterpolateElement(
					__(
						'You agree to comply with Google’s terms and policies, including <policylink>Shopping ads policies</policylink> and <termslink>Google Ads Terms and Conditions</termslink>.',
						'google-listings-and-ads'
					),
					{
						policylink: (
							<AppDocumentationLink
								context="setup-ads"
								linkId="shopping-ads-policies"
								href="https://support.google.com/merchants/answer/6149970"
							/>
						),
						termslink: (
							<AppDocumentationLink
								context="setup-ads"
								linkId="google-ads-terms-of-service"
								href="https://support.google.com/adspolicy/answer/54818"
							/>
						),
					}
				) }
			</p>
			<CheckboxControl
				label={ __(
					'I have read and accept these terms',
					'google-listings-and-ads'
				) }
				checked={ agree }
				onChange={ setAgree }
			/>
		</AppModal>
	);
};

export default TermsModal;

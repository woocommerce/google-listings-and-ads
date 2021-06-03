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
import './index.scss';

const TermsModal = ( props ) => {
	const { onCreateAccount = () => {}, onRequestClose = () => {} } = props;
	const [ agree, setAgree ] = useState( false );

	const handleCreateAccountClick = () => {
		onCreateAccount();
		onRequestClose();
	};

	return (
		<AppModal
			className="gla-mc-terms-modal"
			title={ __(
				'Create Google Merchant Center Account',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="1"
					isPrimary
					disabled={ ! agree }
					eventName="gla_mc_account_create_button_click"
					onClick={ handleCreateAccountClick }
				>
					{ __( 'Create account', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p className="main">
				{ __(
					'By creating a Google Merchant Center account, you agree to the following terms and conditions:',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ createInterpolateElement(
					__(
						'You agree to comply with Google’s terms and policies, including <link>Google Merchant Center Terms of Service</link>.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="setup-mc"
								linkId="google-mc-terms-of-service"
								href="https://support.google.com/merchants/answer/160173"
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

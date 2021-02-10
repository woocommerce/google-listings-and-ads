/**
 * External dependencies
 */
import { Button, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';

const TermsModal = ( props ) => {
	const { onRequestClose } = props;
	const [ agree, setAgree ] = useState( false );

	const handleCreateAccountClick = () => {
		onRequestClose( agree );
	};

	return (
		<AppModal
			title={ __(
				'Create Google Merchant Center Account',
				'google-listings-and-ads'
			) }
			buttons={ [
				<Button
					key="1"
					isPrimary
					disabled={ ! agree }
					onClick={ handleCreateAccountClick }
				>
					{ __( 'Create account', 'google-listings-and-ads' ) }
				</Button>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p>
				{ __(
					'By creating a Google Merchant Center account, you agree to the following terms and conditions:',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'You agree to comply with Google’s terms and policies, including Google Merchant Center Terms of Service.',
					'google-listings-and-ads'
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

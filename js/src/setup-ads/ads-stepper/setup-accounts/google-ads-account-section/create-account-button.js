/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TermsModal from './terms-modal';
import AppButton from '.~/components/app-button';

const CreateAccountButton = ( props ) => {
	const { onCreateAccount, ...rest } = props;
	const [ isOpen, setOpen ] = useState( false );

	const handleButtonClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<>
			<AppButton { ...rest } onClick={ handleButtonClick }>
				{ __( 'Create Account', 'google-listings-and-ads' ) }
			</AppButton>
			{ isOpen && (
				<TermsModal
					onCreateAccount={ onCreateAccount }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default CreateAccountButton;

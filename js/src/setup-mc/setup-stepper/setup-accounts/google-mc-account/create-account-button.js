/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TermsModal from './terms-modal';

const CreateAccountButton = () => {
	const [ isOpen, setOpen ] = useState( false );

	const handleCreateAccountClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<>
			<Button isSecondary onClick={ handleCreateAccountClick }>
				{ __( 'Create Account', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<TermsModal onRequestClose={ handleModalRequestClose } />
			) }
		</>
	);
};

export default CreateAccountButton;

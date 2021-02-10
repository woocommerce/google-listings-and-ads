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
import { useAppDispatch } from '.~/data';

const CreateAccountButton = () => {
	const [ isOpen, setOpen ] = useState( false );
	const { createMCAccount } = useAppDispatch();

	const handleCreateAccountClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = ( agree ) => {
		setOpen( false );

		if ( agree ) {
			createMCAccount();
		}
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

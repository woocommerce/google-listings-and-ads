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

const CreateAccountButton = ( props ) => {
	const { onCreateAccount, ...rest } = props;
	const [ isOpen, setOpen ] = useState( false );

	const handleCreateAccountClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<>
			<Button { ...rest } onClick={ handleCreateAccountClick }>
				{ __( 'Create Account', 'google-listings-and-ads' ) }
			</Button>
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

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

/**
 * Renders a Google Ads account creaton button.
 * When clicking on the button, it will pop up a modal first to ask for terms agreement.
 *
 * @param {Object} props React props.
 * @param {Function} [props.onCreateAccount] Called after the user accept the terms agreement.
 */
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
			<AppButton isSecondary { ...rest } onClick={ handleButtonClick }>
				{ __( 'Create account', 'google-listings-and-ads' ) }
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

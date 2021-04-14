/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppTextButton from '.~/components/app-text-button';
import EditProgramPromptModal from './edit-program-prompt-modal';

const EditProgramButton = ( props ) => {
	const { programId } = props;
	const [ isOpen, setOpen ] = useState( false );

	const handleClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<>
			<AppTextButton isSecondary onClick={ handleClick }>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</AppTextButton>
			{ isOpen && (
				<EditProgramPromptModal
					programId={ programId }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default EditProgramButton;

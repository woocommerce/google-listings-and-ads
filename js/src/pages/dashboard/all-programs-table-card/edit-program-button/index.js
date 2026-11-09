/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useToggle from '~/hooks/useToggle';
import EditProgramPromptModal from './edit-program-prompt-modal';

/**
 * Renders an "Edit" button that opens the edit program prompt modal.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name for the button.
 * @param {string} props.programId The program's identifier.
 * @param {Function} [props.onClick] Called after the modal is opened.
 */
const EditProgramButton = ( props ) => {
	const { className, programId, onClick = () => {}, ...buttonProps } = props;
	const [ isOpen, toggleModal, setOpen ] = useToggle();

	/**
	 * Opens the modal, then forwards the click to the caller's `onClick`
	 * so a consumer-provided handler is not overridden.
	 *
	 * @param {...*} args Arguments passed by the button's click event.
	 */
	const handleClick = ( ...args ) => {
		toggleModal();
		onClick( ...args );
	};

	return (
		<>
			<AppButton
				{ ...buttonProps }
				isLink
				className={ classnames( className ) }
				onClick={ handleClick }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</AppButton>
			{ isOpen && (
				<EditProgramPromptModal
					programId={ programId }
					onRequestClose={ () => setOpen( false ) }
				/>
			) }
		</>
	);
};

export default EditProgramButton;

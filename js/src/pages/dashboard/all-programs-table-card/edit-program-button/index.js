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

const EditProgramButton = ( props ) => {
	const { className, programId, ...buttonProps } = props;
	const [ isOpen, toggleModal ] = useToggle();

	return (
		<>
			<AppButton
				{ ...buttonProps }
				isLink
				className={ classnames( className ) }
				onClick={ toggleModal }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</AppButton>
			{ isOpen && (
				<EditProgramPromptModal
					programId={ programId }
					onRequestClose={ toggleModal }
				/>
			) }
		</>
	);
};

export default EditProgramButton;

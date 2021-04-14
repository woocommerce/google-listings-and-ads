/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppTextButton from '.~/components/app-text-button';
import EditProgramPromptModal from './edit-program-prompt-modal';
import AppModalButton from '.~/components/app-modal-button';

const EditProgramButton = ( props ) => {
	const { programId } = props;

	return (
		<AppModalButton
			button={
				<AppTextButton isSecondary>
					{ __( 'Edit', 'google-listings-and-ads' ) }
				</AppTextButton>
			}
			modal={ <EditProgramPromptModal programId={ programId } /> }
		/>
	);
};

export default EditProgramButton;

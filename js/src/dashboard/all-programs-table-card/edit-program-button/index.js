/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppTextButton from '.~/components/app-text-button';
import EditProgramPromptModal from './edit-program-prompt-modal';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';

const EditProgramButton = ( props ) => {
	const { programId } = props;

	return (
		<AppButtonModalTrigger
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

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import EditProgramPromptModal from './edit-program-prompt-modal';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';

const EditProgramButton = ( props ) => {
	const { programId } = props;

	return (
		<AppButtonModalTrigger
			button={
				<Button isLink>
					{ __( 'Edit', 'google-listings-and-ads' ) }
				</Button>
			}
			modal={ <EditProgramPromptModal programId={ programId } /> }
		/>
	);
};

export default EditProgramButton;

/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import EditProgramPromptModal from './edit-program-prompt-modal';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';

const EditProgramButton = ( props ) => {
	const { className, programId } = props;

	return (
		<AppButtonModalTrigger
			button={
				<AppButton isLink className={ classnames( className ) }>
					{ __( 'Edit', 'google-listings-and-ads' ) }
				</AppButton>
			}
			modal={ <EditProgramPromptModal programId={ programId } /> }
		/>
	);
};

export default EditProgramButton;

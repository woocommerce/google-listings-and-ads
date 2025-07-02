/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import { getEditCampaignUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';
import './edit-program-prompt-modal.scss';

/**
 * Triggered when "continue" to edit program button is clicked.
 *
 * @event gla_dashboard_edit_program_click
 * @property {string} program_id program id
 * @property {string} url URL for editing the program (paid campaign)
 */

/**
 * @fires gla_dashboard_edit_program_click when "Continue to edit" is clicked.
 * @param {Object} props React props.
 * @param {string} props.programId The program's identifier
 * @param {Function} props.onRequestClose Callback function when closing the modal.
 * @return {JSX.Element} `AppModal` with content.
 */
const EditProgramPromptModal = ( { programId, onRequestClose } ) => {
	const handleDontEditClick = () => {
		onRequestClose();
	};

	const handleContinueEditClick = () => {
		const url = getEditCampaignUrl( programId );

		getHistory().push( url );

		recordGlaEvent( 'gla_dashboard_edit_program_click', {
			program_id: programId,
			url,
		} );
	};

	return (
		<AppModal
			className="gla-edit-program-prompt-modal"
			title={ __( 'Before you edit…', 'google-listings-and-ads' ) }
			buttons={ [
				<AppButton key="no" isSecondary onClick={ handleDontEditClick }>
					{ __( `Don't edit`, 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="yes"
					isPrimary
					onClick={ handleContinueEditClick }
				>
					{ __( 'Continue to edit', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p>
				{ __(
					'Results typically improve with time.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'Editing will result in the loss of any optimisations learned over time.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'We recommend allowing your programs to run for at least 14 days after set up, without pausing or editing, for optimal performance.',
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
};

export default EditProgramPromptModal;

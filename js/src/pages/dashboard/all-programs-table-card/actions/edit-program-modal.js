/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getEditCampaignUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * Triggered when "continue" to edit program button is clicked.
 *
 * @event gla_dashboard_edit_program_click
 * @property {string} program_id program id
 * @property {string} url URL for editing the program (paid campaign)
 */

/**
 * Content of the DataViews modal of the Edit program action, prompting before navigating to the edit page.
 *
 * @fires gla_dashboard_edit_program_click when "Continue to edit" is clicked.
 * @param {Object} props React props.
 * @param {Array<Object>} props.items The programs the action is performed on. Only the first one is used.
 * @param {Function} props.closeModal Callback to close the modal.
 */
const EditProgramModal = ( { items, closeModal } ) => {
	const [ program ] = items;

	const handleContinueEditClick = () => {
		const url = getEditCampaignUrl( program.id );

		getHistory().push( url );

		recordGlaEvent( 'gla_dashboard_edit_program_click', {
			program_id: program.id,
			url,
		} );
	};

	return (
		<>
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
			<Flex justify="flex-end">
				<AppButton isSecondary onClick={ closeModal }>
					{ __( `Don't edit`, 'google-listings-and-ads' ) }
				</AppButton>
				<AppButton isPrimary onClick={ handleContinueEditClick }>
					{ __( 'Continue to edit', 'google-listings-and-ads' ) }
				</AppButton>
			</Flex>
		</>
	);
};

export default EditProgramModal;

/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { FREE_LISTINGS_PROGRAM_ID } from '.~/constants';
import AppModal from '.~/components/app-modal';
import recordEvent from '.~/utils/recordEvent';
import './index.scss';
import { getEditFreeListingsUrl, getEditCampaignUrl } from '.~/utils/urls';

const EditProgramPromptModal = ( props ) => {
	const { programId, onRequestClose } = props;

	const handleDontEditClick = () => {
		onRequestClose();
	};

	const handleContinueEditClick = () => {
		const url =
			programId === FREE_LISTINGS_PROGRAM_ID
				? getEditFreeListingsUrl()
				: getEditCampaignUrl( programId );

		getHistory().push( url );

		recordEvent( 'gla_dashboard_edit_program_click', {
			programId,
			url,
		} );
	};

	return (
		<AppModal
			className="gla-edit-program-prompt-modal"
			title={ __( 'Before you edit…', 'google-listings-and-ads' ) }
			buttons={ [
				<Button key="no" isSecondary onClick={ handleDontEditClick }>
					{ __( `Don't edit`, 'google-listings-and-ads' ) }
				</Button>,
				<Button key="yes" isPrimary onClick={ handleContinueEditClick }>
					{ __( 'Continue to edit', 'google-listings-and-ads' ) }
				</Button>,
			] }
			onRequestClose={ onRequestClose }
		>
			<p>
				{ __(
					'Results typically improve with time with Google’s Free Listing and paid ad campaigns.',
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

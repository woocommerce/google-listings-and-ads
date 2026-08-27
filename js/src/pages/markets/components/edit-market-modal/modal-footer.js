/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppButton from '~/components/app-button';

const CONTEXT = 'edit_market_modal';

/**
 * Event fired when the "Cancel" button in the EditMarketModal is clicked.
 * @event gla_cancel_button_clicked
 * @property {string} context The context in which the cancel button click happened, e.g. "edit_market_modal".
 */
/**
 * Event fired when the "Save" button in the EditMarketModal is clicked.
 * @event gla_save_button_clicked
 * @property {string} context The context in which the save button click happened, e.g. "edit_market_modal".
 */
/**
 * Modal footer component for the EditMarketModal.
 * It renders a cancel button and a save button.
 * It also shows a validation error if the form is not valid.
 *
 * @fires gla_cancel_button_clicked when the cancel button is clicked with context of "edit_market_modal"
 * @fires gla_save_button_clicked when the save button is clicked with context of "edit_market_modal"
 *
 * @param {Object}   props
 * @param {Function} props.onCancel Called when the cancel button is clicked.
 */
const ModalFooter = ( { onCancel } ) => {
	const { adapter, isValidForm, handleSubmit } = useAdaptiveFormContext();
	const { isSaving } = adapter;

	const handleSubmitClick = ( event ) => {
		if ( isValidForm ) {
			return handleSubmit( event );
		}
		adapter.showValidation();
	};

	return (
		<div className="app-modal__footer">
			<AppButton
				disabled={ isSaving }
				eventName="gla_cancel_button_clicked"
				eventProps={ { context: CONTEXT } }
				onClick={ onCancel }
				variant="tertiary"
			>
				{ __( 'Cancel', 'google-listings-and-ads' ) }
			</AppButton>
			<AppButton
				eventName="gla_save_button_clicked"
				eventProps={ { context: CONTEXT } }
				loading={ isSaving }
				onClick={ handleSubmitClick }
				variant="primary"
			>
				{ __( 'Save', 'google-listings-and-ads' ) }
			</AppButton>
		</div>
	);
};

export default ModalFooter;

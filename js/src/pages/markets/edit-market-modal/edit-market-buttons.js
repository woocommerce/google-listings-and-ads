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
 * @param {Object}   props
 * @param {Function} props.onRequestClose Called when the cancel button is clicked.
 */
const EditMarketButtons = ( { onRequestClose } ) => {
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
				variant="tertiary"
				onClick={ onRequestClose }
				disabled={ isSaving }
				eventName="gla_cancel_button_clicked"
				eventProps={ { context: CONTEXT } }
			>
				{ __( 'Cancel', 'google-listings-and-ads' ) }
			</AppButton>
			<AppButton
				variant="primary"
				onClick={ handleSubmitClick }
				loading={ isSaving }
				eventName="gla_save_button_clicked"
				eventProps={ { context: CONTEXT } }
			>
				{ __( 'Save', 'google-listings-and-ads' ) }
			</AppButton>
		</div>
	);
};

export default EditMarketButtons;

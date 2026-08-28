/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppButton from '~/components/app-button';

const CONTEXT = 'add_market_modal';

/**
 * Event fired when the "Cancel" button in the AddMarketModal is clicked.
 * @event gla_cancel_button_clicked
 * @property {string} context The context in which the cancel button click happened, e.g. "add_market_modal".
 */
/**
 * Event fired when the "Add market" button in the AddMarketModal is clicked.
 * @event gla_add_new_market_button_clicked
 * @property {string} context The context in which the add market button click happened, e.g. "add_market_modal".
 */
/**
 * Modal footer component for the AddMarketModal.
 * It renders a cancel button and an add market button if the shipping rate is not manual.
 * It also shows a validation error if the form is not valid.
 *
 * @fires gla_cancel_button_clicked when the cancel button is clicked with context of "add_market_modal"
 * @fires gla_add_new_market_button_clicked when the add market button is clicked with context of "add_market_modal"
 * @param {Object}   props
 * @param {Function} props.onCancel  Called when the cancel button is clicked.
 * @param {Object}   props.settings        Settings object used to determine button visibility.
 */

const ModalFooter = ( { onCancel, settings } ) => {
	const { adapter, isValidForm, handleSubmit } = useAdaptiveFormContext();
	const { isSaving } = adapter;

	const showAddMarketButton = ! (
		! glaData.isMultiLingualStore &&
		settings.shipping_rate === SHIPPING_RATE_METHOD.MANUAL
	);

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
				variant={ showAddMarketButton ? 'tertiary' : 'primary' }
			>
				{ __( 'Cancel', 'google-listings-and-ads' ) }
			</AppButton>

			{ showAddMarketButton && (
				<AppButton
					eventName="gla_add_new_market_button_clicked"
					eventProps={ { context: CONTEXT } }
					loading={ isSaving }
					onClick={ handleSubmitClick }
					variant="primary"
				>
					{ __( 'Add market', 'google-listings-and-ads' ) }
				</AppButton>
			) }
		</div>
	);
};

export default ModalFooter;

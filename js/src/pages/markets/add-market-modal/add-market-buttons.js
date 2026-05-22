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
 * @param {Object}   props
 * @param {Function} props.onRequestClose  Called when the cancel button is clicked.
 * @param {Object}   props.settings        Settings object used to determine button visibility.
 */
const AddMarketButtons = ( { onRequestClose, settings } ) => {
	const { adapter, isValidForm, handleSubmit } = useAdaptiveFormContext();
	const { isSaving } = adapter;

	const showAddMarketButton = ! (
		! glaData.isMultiLingualStore &&
		settings?.shipping_rate === SHIPPING_RATE_METHOD.MANUAL
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
				variant={ showAddMarketButton ? 'tertiary' : 'primary' }
				onClick={ onRequestClose }
				disabled={ isSaving }
				eventName="gla_cancel_button_clicked"
				eventProps={ { context: CONTEXT } }
			>
				{ __( 'Cancel', 'google-listings-and-ads' ) }
			</AppButton>
			{ showAddMarketButton && (
				<AppButton
					variant="primary"
					onClick={ handleSubmitClick }
					loading={ isSaving }
					eventName="gla_add_new_market_button_clicked"
					eventProps={ { context: CONTEXT } }
				>
					{ __( 'Add market', 'google-listings-and-ads' ) }
				</AppButton>
			) }
		</div>
	);
};

export default AddMarketButtons;

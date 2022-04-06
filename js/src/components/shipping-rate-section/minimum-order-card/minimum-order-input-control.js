/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '.~/components/app-input-price-control';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import MinimumOrderInputControlLabelText from './minimum-order-input-control-label-text';
import EditMinimumOrderFormModal from './edit-minimum-order-form-modal';
import './minimum-order-input-control.scss';

const MinimumOrderInputControl = ( props ) => {
	const { countryOptions, value, onChange } = props;
	const { countries, threshold, currency } = value;

	const handleBlur = ( event, numberValue ) => {
		if ( numberValue === value.threshold ) {
			return;
		}

		onChange( {
			countries,
			threshold: numberValue > 0 ? numberValue : undefined,
			currency,
		} );
	};

	return (
		<AppInputPriceControl
			className="gla-minimum-order-input-control"
			label={
				<div className="gla-minimum-order-input-control__label">
					<MinimumOrderInputControlLabelText
						countries={ countries }
					/>
					<AppButtonModalTrigger
						button={
							<Button
								className="gla-minimum-order-input-control__edit-button"
								isTertiary
							>
								{ __( 'Edit', 'google-listings-and-ads' ) }
							</Button>
						}
						modal={
							<EditMinimumOrderFormModal
								countryOptions={ countryOptions }
								initialValues={ value }
								onSubmit={ onChange }
							/>
						}
					/>
				</div>
			}
			suffix={ currency }
			value={ threshold }
			onBlur={ handleBlur }
		/>
	);
};

export default MinimumOrderInputControl;

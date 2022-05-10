/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '.~/components/app-input-price-control';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import AppButton from '.~/components/app-button';
import { EditRateFormModal } from './rate-form-modals';
import ShippingRateInputControlLabelText from './shipping-rate-input-control-label-text';
import './shipping-rate-input-control.scss';

/**
 * @typedef { import("./typedefs").ShippingRateGroup } ShippingRateGroup
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Input control to edit a shipping rate group.
 *
 * The input control label contains a placeholder area for `button`, after the label text.
 * This is meant to display an "Edit" button.
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Country options to be passed to EditRateFormModal.
 * @param {ShippingRateGroup} props.value Shipping rate group value.
 * @param {(newGroup: ShippingRateGroup) => void} props.onChange Called when shipping rate group changes.
 * @param {() => void} props.onDelete Called when delete button in EditRateFormModal is clicked.
 */
const ShippingRateInputControl = ( {
	countryOptions,
	value,
	onChange,
	onDelete,
} ) => {
	const { countries, currency, rate } = value;

	const handleBlur = ( event, numberValue ) => {
		if ( rate === numberValue ) {
			return;
		}

		onChange( {
			...value,
			rate: numberValue,
		} );
	};

	return (
		<div className="gla-shipping-rate-input-control">
			<AppInputPriceControl
				label={
					<div className="label">
						<ShippingRateInputControlLabelText
							countries={ countries }
						/>
						<AppButtonModalTrigger
							button={
								<AppButton isTertiary>
									{ __( 'Edit', 'google-listings-and-ads' ) }
								</AppButton>
							}
							modal={
								<EditRateFormModal
									countryOptions={ countryOptions }
									initialValues={ value }
									onSubmit={ onChange }
									onDelete={ onDelete }
								/>
							}
						/>
					</div>
				}
				suffix={ currency }
				value={ rate }
				onBlur={ handleBlur }
			/>
			{ rate === 0 && (
				<div className="gla-input-pill-div">
					<Pill>
						{ __(
							'Free shipping for all orders',
							'google-listings-and-ads'
						) }
					</Pill>
				</div>
			) }
		</div>
	);
};

export default ShippingRateInputControl;

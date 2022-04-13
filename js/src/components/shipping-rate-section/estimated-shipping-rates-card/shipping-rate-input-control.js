/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import AppButton from '.~/components/app-button';
import AppInputPriceControl from '.~/components/app-input-price-control';
import EditRateFormModal from './edit-rate-form-modal';
import './shipping-rate-input-control.scss';
import ShippingRateInputControlLabelText from './shipping-rate-input-control-label-text';

/**
 * @typedef { import("./typedefs").ShippingRateGroup } ShippingRateGroup
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Input control to edit a shipping rate.
 * Consists of a simple input field to adjust the rate
 * and with a modal with a more advanced form to select countries.
 *
 * @param {Object} props
 * @param {ShippingRateGroup} props.value Aggregate, rat: Array object to be used as the initial value.
 * @param {Array<CountryCode>} props.countryOptions Array of country codes options, to be used as options in AppCountrySelect.
 * @param {(newRate: ShippingRateGroup, deletedCountries: Array<CountryCode>|undefined) => void} props.onChange Called when rate changes.
 * @param {() => void} props.onDelete Called when users clicked on the Delete button.
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
								<AppButton
									className="gla-shipping-rate-input-control__edit-button"
									isTertiary
								>
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

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pill } from '@woocommerce/components';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import AppButton from '.~/components/app-button';
import AppInputPriceControl from '.~/components/app-input-price-control';
import ShippingRateInputControlLabelText from './shipping-rate-input-control-label-text';
import './shipping-rate-input-control.scss';

/**
 * @typedef { import("./typedefs").ShippingRateGroup } ShippingRateGroup
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Input control to edit a shipping rate group.
 *
 * The input control label contains an edit button.
 * Upon clicking on the edit button, it will display the modal passed in via `editButtonModal` prop.
 *
 * @param {Object} props
 * @param {JSX.Element} props.editButtonModal Modal to be displayed when the Edit button is clicked.
 * @param {ShippingRateGroup} props.value Aggregate, rat: Array object to be used as the initial value.
 * @param {(newGroup: ShippingRateGroup) => void} props.onChange Called when shipping rate group changes.
 */
const ShippingRateInputControl = ( {
	editButtonModal,
	value,
	onChange = noop,
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
							modal={ editButtonModal }
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

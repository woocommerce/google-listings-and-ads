/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pill } from '@woocommerce/components';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
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
 * The input control label contains a placeholder area for `button`, after the label text.
 * This is meant to display an "Edit" button.
 *
 * @param {Object} props
 * @param {JSX.Element} props.labelButton Button to be displayed after the label text.
 * @param {ShippingRateGroup} props.value Shipping rate group value.
 * @param {(newGroup: ShippingRateGroup) => void} props.onChange Called when shipping rate group changes.
 */
const ShippingRateInputControl = ( {
	labelButton,
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
						{ labelButton }
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

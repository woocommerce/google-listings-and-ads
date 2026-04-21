/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '~/components/app-input-price-control';
import ShippingRateInputControlLabelText from './shipping-rate-input-control-label-text';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import './shipping-rate-input-control.scss';

/**
 * @typedef { import("~/data/actions").CountryCode } CountryCode
 */

/**
 * Input control to edit a shipping rate.
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Country options.
 * @param {number} props.value The shipping rate this control is responsible for.
 * @param {(newRate: number) => void} props.onChange Callback called with the new rate when the rate is changed.
 */
const ShippingRateInputControl = ( { countryOptions, value, onChange } ) => {
	const { code: currencyCode } = useStoreCurrency();

	const handleBlur = ( event, numberValue ) => {
		if ( value === numberValue ) {
			return;
		}

		onChange( numberValue );
	};

	return (
		<div className="gla-shipping-rate-input-control">
			<AppInputPriceControl
				label={
					<ShippingRateInputControlLabelText
						countries={ countryOptions }
					/>
				}
				suffix={ currencyCode }
				value={ value }
				onBlur={ handleBlur }
				hideLabelFromVision
			/>

			{ value === 0 && (
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

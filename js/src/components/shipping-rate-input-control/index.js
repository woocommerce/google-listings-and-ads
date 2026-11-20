/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '~/components/app-input-price-control';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import './index.scss';

/**
 * @typedef { import("~/data/actions").CountryCode } CountryCode
 */

/**
 * Input control to edit a shipping rate.
 *
 * @param {Object} props
 * @param {JSX.Element|string} props.label Label content for the input control.
 * @param {number} props.value The shipping rate this control is responsible for.
 * @param {(newRate: number) => void} props.onChange Callback called with the new rate when the rate is changed.
 * @param {boolean} [props.hideLabelFromVision] Whether the label should be hidden from vision. Default to true.
 */
const ShippingRateInputControl = ( {
	label,
	value,
	onChange,
	hideLabelFromVision = true,
} ) => {
	const { code: currencyCode } = useStoreCurrency();

	const handleBlur = ( event, numberValue ) => {
		if (
			value === numberValue ||
			isNaN( numberValue ) ||
			numberValue < 0
		) {
			return;
		}

		onChange( numberValue );
	};

	return (
		<div className="gla-shipping-rate-input-control">
			<AppInputPriceControl
				label={ label }
				suffix={ currencyCode }
				value={ value }
				onBlur={ handleBlur }
				hideLabelFromVision={ hideLabelFromVision }
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

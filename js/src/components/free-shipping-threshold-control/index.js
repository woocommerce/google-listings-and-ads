/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';
import AppInputPriceControl from '~/components/app-input-price-control';
import OfferFreeShippingCheckbox from '~/components/order-value-condition-section/offer-free-shipping-checkbox';

/**
 * @typedef { import("~/data/actions").ShippingRate } ShippingRate
 */

/**
 * Renders controls to set free shipping threshold for minimum order value condition.
 *
 * @param {Object} props React props.
 * @param {number} props.threshold The current free shipping threshold.
 * @param {string} props.currency The currency to display for the threshold.
 * @param {(nextValue: number) => void} props.onChange Callback called with the updated threshold once it changes.
 */
const FreeShippingThresholdControl = ( { onChange, threshold, currency } ) => {
	const offerFreeShippingInputProps = useAdaptiveFormInputProps(
		'offer_free_shipping'
	);
	const { values } = useAdaptiveFormContext();

	const handleBlur = ( _event, numberValue ) => {
		if (
			numberValue === threshold ||
			isNaN( numberValue ) ||
			numberValue < 0
		) {
			return;
		}

		onChange( numberValue );
	};

	return (
		<>
			<OfferFreeShippingCheckbox { ...offerFreeShippingInputProps } />

			{ values.offer_free_shipping && (
				<AppInputPriceControl
					label={ __( 'Cost', 'google-listings-and-ads' ) }
					onBlur={ handleBlur }
					suffix={ currency }
					value={ threshold }
				/>
			) }
		</>
	);
};

export default FreeShippingThresholdControl;

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '~/components/app-input-price-control';
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';
import OfferFreeShippingCheckbox from '~/components/order-value-condition-section/offer-free-shipping-checkbox';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import './minimum-order-card.scss';

/**
 * @typedef { import("~/data/actions").ShippingRate } ShippingRate
 */

/**
 * Renders controls to set free shipping threshold for minimum order value condition.
 *
 * @param {Object} props React props.
 * @param {Array<ShippingRate>} [props.value=[]] Array of shipping rates; the threshold is read from the first non-free rate and written back to all rates.
 * @param {(nextValue: Array<ShippingRate>) => void} props.onChange Callback called with the updated rates once the threshold changes.
 */
const FreeShippingThresholdControl = ( { value, onChange } ) => {
	const offerFreeShippingInputProps = useAdaptiveFormInputProps(
		'offer_free_shipping'
	);
	const { values } = useAdaptiveFormContext();

	const nonFreeRates = value.filter( isNonFreeShippingRate );
	const threshold = nonFreeRates[ 0 ]?.options?.free_shipping_threshold;
	const currency = value[ 0 ]?.currency;

	const handleBlur = ( _event, numberValue ) => {
		if ( numberValue === threshold ) {
			return;
		}
		onChange(
			value.map( ( rate ) => {
				if ( ! isNonFreeShippingRate( rate ) ) {
					return rate;
				}
				return {
					...rate,
					options: {
						...rate.options,
						free_shipping_threshold:
							numberValue > 0 ? numberValue : undefined,
					},
				};
			} )
		);
	};

	return (
		<>
			<OfferFreeShippingCheckbox { ...offerFreeShippingInputProps } />

			{ values.offer_free_shipping && (
				<AppInputPriceControl
					label={ __( 'Cost', 'google-listings-and-ads' ) }
					suffix={ currency }
					value={ threshold }
					onBlur={ handleBlur }
				/>
			) }
		</>
	);
};

export default FreeShippingThresholdControl;

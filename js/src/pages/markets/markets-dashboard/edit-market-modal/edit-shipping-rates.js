/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ShippingRateInputControl from '~/components/shipping-rate-section/estimated-shipping-rates-card/shipping-rate-input-control';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import MinimumOrderInputs from '~/components/minimum-order-inputs';

const EditShippingRates = ( { audienceCountryCodes = [] } ) => {
	const { values, setValue, getInputProps } = useAdaptiveFormContext();

	const handleRateChange = ( nextRate ) => {
		setValue( 'flat_shipping_rate', nextRate );
	};

	const handleThresholdBlur = ( _event, numberValue ) => {
		setValue(
			'free_shipping_threshold',
			numberValue > 0 ? numberValue : undefined
		);
	};

	return (
		<div className="gla-edit-estimated-rates">
			<p className="gla-edit-estimated-rates__label">
				{ __(
					'Estimated shipping rates',
					'google-listings-and-ads'
				) }
			</p>
			<ShippingRateInputControl
				countryOptions={ audienceCountryCodes }
				value={ values.flat_shipping_rate }
				onChange={ handleRateChange }
			/>
			<MinimumOrderInputs
				offerFreeShippingInputProps={ getInputProps( 'offer_free_shipping' ) }
				offerFreeShipping={ values.offer_free_shipping }
				currency={ values.shipping_currency }
				threshold={ values.free_shipping_threshold }
				onCostBlur={ handleThresholdBlur }
			/>
		</div>
	);
};

export default EditShippingRates;

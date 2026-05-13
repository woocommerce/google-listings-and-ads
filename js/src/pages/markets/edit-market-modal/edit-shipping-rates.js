/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useSettings from '~/hooks/useSettings';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import ShippingRateInputControl from '~/components/shipping-rate-input-control';
import FreeShippingThresholdControl from '~/components/free-shipping-threshold-control';
import MinimumOrderCard from '~/components/order-value-condition-section/minimum-order-card';
import AppInputPriceControl from '~/components/app-input-price-control';

const EditShippingRates = () => {
	const {
		getInputProps,
		values,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { settings } = useSettings();
	const { code: currencyCode } = useStoreCurrency();

	if ( settings?.shipping_rate !== SHIPPING_RATE_METHOD.FLAT ) {
		return null;
	}

	const { value: threshold, onChange: onThresholdChange } =
		getInputProps( 'free_shipping' );

	// const handleChange = ( numberValue ) => {
	// 	if ( threshold === numberValue ) {
	// 		return;
	// 	}
	// 	onThresholdChange( numberValue > 0 ? numberValue : null );
	// };

	return (
		<div className="gla-edit-shipping-rates">
			<ShippingRateInputControl
				className="gla-edit-shipping-rates__rate"
				hideLabelFromVision={ false }
				label={ __(
					'Estimated shipping rates',
					'google-listings-and-ads'
				) }
				{ ...getInputProps( 'flat_shipping_rate' ) }
			/>
			<FreeShippingThresholdControl
				onChange={ onThresholdChange }
				threshold={ threshold }
				currency={ currencyCode }
			/>
			{ renderRequestedValidation( 'free_shipping' ) }
		</div>
	);
};

export default EditShippingRates;

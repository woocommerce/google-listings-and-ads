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
import ShippingRateInputControl from '~/components/shipping-rate-section/estimated-shipping-rates-card/shipping-rate-input-control';
import OfferFreeShippingCheckbox from '~/components/order-value-condition-section/offer-free-shipping-checkbox';
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

	const handleThresholdBlur = ( _event, numberValue ) => {
		if ( threshold === numberValue ) {
			return;
		}
		onThresholdChange( numberValue > 0 ? numberValue : null );
	};

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
			<OfferFreeShippingCheckbox
				className="gla-edit-shipping-rates__free-shipping"
				{ ...getInputProps( 'offer_free_shipping' ) }
			/>
			{ values.offer_free_shipping && (
				<AppInputPriceControl
					className="gla-edit-shipping-rates__free-shipping-cost"
					label={ __( 'Cost', 'google-listings-and-ads' ) }
					suffix={ currencyCode }
					value={ threshold }
					onBlur={ handleThresholdBlur }
				/>
			) }
			{ renderRequestedValidation( 'free_shipping' ) }
		</div>
	);
};

export default EditShippingRates;

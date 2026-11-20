/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import ShippingRateInputControl from '~/components/shipping-rate-input-control';

/**
 * Renders the shipping rate input control in the market form.
 */
const ShippingRateControl = () => {
	const { getInputProps } = useAdaptiveFormContext();

	return (
		<ShippingRateInputControl
			hideLabelFromVision={ false }
			label={ __(
				'Estimated shipping rates',
				'google-listings-and-ads'
			) }
			{ ...getInputProps( 'flat_shipping_rate' ) }
		/>
	);
};

export default ShippingRateControl;

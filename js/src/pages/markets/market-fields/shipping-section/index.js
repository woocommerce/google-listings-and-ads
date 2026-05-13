/**
 * Internal dependencies
 */
import ShippingRateControl from './shipping-rate-control';
import FreeShippingThresholdField from './free-shipping-threshold-field';
import ShippingTimesInput from './shipping-times-input';

/**
 * Renders the shipping section of the market form, which includes controls for
 * 1. shipping rates,
 * 2. free shipping thresholds,
 * 3. estimated shipping times.
 * This section is conditionally rendered based on the store's settings and multilingual support.
 */
const ShippingSection = () => {
	return (
		<>
			<ShippingRateControl />
			<FreeShippingThresholdField />
			<ShippingTimesInput />
		</>
	);
};

export default ShippingSection;

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import useSettings from '~/hooks/useSettings';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import ShippingTimesInput from './shipping-times-input';
import ShippingNotice from './shipping-notice';
import ShippingRateControls from './shipping-rate-controls';

/**
 * Renders the shipping section of the market form, which includes controls for
 * 1. shipping rates,
 * 2. free shipping thresholds,
 * 3. estimated shipping times.
 * This section is conditionally rendered based on the store's settings and multilingual support.
 */
const ShippingSection = () => {
	const { settings } = useSettings();
	const { adapter } = useAdaptiveFormContext();
	const { isPrimaryMarket } = adapter;

	if ( settings?.shipping_rate === SHIPPING_RATE_METHOD.MANUAL ) {
		// ShippingNotice only renders for multilingual stores or the primary market.
		if ( ! glaData.isMultiLingualStore && ! isPrimaryMarket ) {
			return null;
		}
		return <ShippingNotice />;
	}

	return (
		<>
			<ShippingRateControls />
			<ShippingTimesInput />
		</>
	);
};

export default ShippingSection;

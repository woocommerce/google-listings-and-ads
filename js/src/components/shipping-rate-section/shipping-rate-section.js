/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { SHIPPING_RATE_METHOD } from '~/constants';
import FlatShippingRatesInputCards from './flat-shipping-rates-input-cards';
import ShippingRateMethodSection from './shipping-rate-method-section';

/**
 * Renders the shipping rate method section on the Settings page, including the
 * flat shipping rates input cards if the store is not multilingual and the
 * selected shipping rate method is flat.
 */
const ShippingRateSection = () => {
	const { values } = useAdaptiveFormContext();

	return (
		<ShippingRateMethodSection>
			{ values.shipping_rate === SHIPPING_RATE_METHOD.FLAT && (
				<FlatShippingRatesInputCards />
			) }
		</ShippingRateMethodSection>
	);
};

export default ShippingRateSection;

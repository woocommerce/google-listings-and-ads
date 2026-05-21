/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import FlatShippingRatesInputCards from './flat-shipping-rates-input-cards';
import ShippingRateMethodSection from './shipping-rate-method-section';

const ShippingRateSection = () => {
	const { values } = useAdaptiveFormContext();
	const { isMultiLingualStore } = glaData;

	return (
		<ShippingRateMethodSection>
			{ ! isMultiLingualStore &&
				values.shipping_rate === SHIPPING_RATE_METHOD.FLAT && (
					<FlatShippingRatesInputCards />
				) }
		</ShippingRateMethodSection>
	);
};

export default ShippingRateSection;

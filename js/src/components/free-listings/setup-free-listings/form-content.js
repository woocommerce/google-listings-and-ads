/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { SHIPPING_RATE_METHOD, SHIPPING_TIME_METHOD } from '~/constants';
import ChooseAudienceSection from '~/components/free-listings/choose-audience-section';
import ShippingRateSection from '~/components/shipping-rate-section';
import ShippingTimeSection from '~/components/free-listings/configure-product-listings/shipping-time-section';
import OrderValueConditionSection from '~/components/order-value-condition-section';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';

/**
 * Form to configure free listigns.
 */
const FormContent = () => {
	const { values } = useAdaptiveFormContext();

	const hasCountries =
		values.location === 'all' || values.countries.length > 0;
	const shouldDisplayShippingTime =
		values.shipping_time === SHIPPING_TIME_METHOD.FLAT && hasCountries;
	const shouldDisplayShippingRate = hasCountries;
	const shouldDisplayOrderValueCondition =
		values.shipping_rate === SHIPPING_RATE_METHOD.FLAT &&
		values.shipping_country_rates.some( isNonFreeShippingRate );

	return (
		<>
			<ChooseAudienceSection />
			{ shouldDisplayShippingRate && <ShippingRateSection /> }
			{ shouldDisplayOrderValueCondition && (
				<OrderValueConditionSection />
			) }
			{ shouldDisplayShippingTime && <ShippingTimeSection /> }
		</>
	);
};

export default FormContent;

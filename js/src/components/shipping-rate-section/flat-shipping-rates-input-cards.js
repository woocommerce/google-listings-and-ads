/**
 * Internal dependencies
 */
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';

const FlatShippingRatesInputCards = () => {
	const { adapter } = useAdaptiveFormContext();
	const inputProps = useAdaptiveFormInputProps( 'flat_shipping_rate' );

	return (
		<EstimatedShippingRatesCard
			audienceCountries={ adapter.audienceCountries }
			{ ...inputProps }
		/>
	);
};

export default FlatShippingRatesInputCards;

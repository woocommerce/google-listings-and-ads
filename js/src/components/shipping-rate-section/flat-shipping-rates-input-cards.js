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
	const { value, onChange, helper } =
		useAdaptiveFormInputProps( 'flat_shipping_rate' );

	return (
		<EstimatedShippingRatesCard
			audienceCountries={ adapter.audienceCountries }
			value={ value }
			onChange={ onChange }
			helper={ helper }
		/>
	);
};

export default FlatShippingRatesInputCards;

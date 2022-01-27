/**
 * Internal dependencies
 */
import { EstimatedShippingRatesCard } from './estimated-shipping-rates-card';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;

	return (
		<EstimatedShippingRatesCard
			audienceCountries={ audienceCountries }
			value={ value }
			onChange={ onChange }
		/>
	);
};

export default FlatShippingRatesInputCards;

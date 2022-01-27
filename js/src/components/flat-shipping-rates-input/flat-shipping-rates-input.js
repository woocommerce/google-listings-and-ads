/**
 * Internal dependencies
 */
import { EstimatedShippingRatesCard } from './estimated-shipping-rates-card';

const FlatShippingRatesInput = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;

	return (
		<EstimatedShippingRatesCard
			audienceCountries={ audienceCountries }
			value={ value }
			onChange={ onChange }
		/>
	);
};

export default FlatShippingRatesInput;

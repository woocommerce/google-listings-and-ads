/**
 * Internal dependencies
 */
import FreeShippingCards from './free-shipping-cards';
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';
import { SHIPPING_RATE_METHOD } from '.~/constants';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;
	const displayFreeShippingCards = value.some(
		( shippingRate ) =>
			shippingRate.rate > 0 &&
			shippingRate.method === SHIPPING_RATE_METHOD.FLAT_RATE
	);

	return (
		<>
			<EstimatedShippingRatesCard
				audienceCountries={ audienceCountries }
				value={ value }
				onChange={ onChange }
			/>
			{ displayFreeShippingCards && (
				<FreeShippingCards value={ value } onChange={ onChange } />
			) }
		</>
	);
};

export default FlatShippingRatesInputCards;

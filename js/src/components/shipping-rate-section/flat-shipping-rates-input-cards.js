/**
 * Internal dependencies
 */
import isNonFreeFlatShippingRate from '.~/utils/isNonFreeFlatShippingRate';
import FreeShippingCards from './free-shipping-cards';
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;
	const displayFreeShippingCards = value.some( isNonFreeFlatShippingRate );

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

/**
 * Internal dependencies
 */
import isNonZeroFlatShippingRate from '.~/utils/isNonZeroFlatShippingRate';
import FreeShippingCards from './free-shipping-cards';
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;
	const displayFreeShippingCards = value.some( isNonZeroFlatShippingRate );

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

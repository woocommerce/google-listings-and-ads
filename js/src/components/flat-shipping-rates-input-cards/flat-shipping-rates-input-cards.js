/**
 * Internal dependencies
 */
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';
import FreeShippingCards from './free-shipping-cards';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;
	const displayFreeShippingCards = value.some( ( el ) => el.rate > 0 );

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

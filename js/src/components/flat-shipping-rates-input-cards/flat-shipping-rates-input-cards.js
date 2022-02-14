/**
 * Internal dependencies
 */
import FreeShippingCards from './free-shipping-cards';
import ShippingCountriesForm from './shipping-rate-setup/countries-form';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;
	const displayFreeShippingCards = value.some( ( el ) => el.rate > 0 );

	return (
		<>
			<ShippingCountriesForm
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

/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';
import OfferFreeShippingCard from './offer-free-shipping-card';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;
	const displayOfferFreeShippingCard = value.some( ( el ) => el.rate > 0 );
	const initialOfferFreeShippingValue = ! displayOfferFreeShippingCard
		? null
		: value.some( ( el ) => el.options?.free_shippping_threshold > 0 );
	const [ offerFreeShippingValue, setOfferFreeShippingValue ] = useState(
		initialOfferFreeShippingValue
	);

	return (
		<>
			<EstimatedShippingRatesCard
				audienceCountries={ audienceCountries }
				value={ value }
				onChange={ onChange }
			/>
			{ displayOfferFreeShippingCard && (
				<OfferFreeShippingCard
					value={ offerFreeShippingValue }
					onChange={ setOfferFreeShippingValue }
				/>
			) }
		</>
	);
};

export default FlatShippingRatesInputCards;

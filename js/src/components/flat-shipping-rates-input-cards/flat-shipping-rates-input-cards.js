/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';
import MinimumOrderCard from './minimum-order-card';
import OfferFreeShippingCard from './offer-free-shipping-card';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, value, onChange = () => {} } = props;

	const displayOfferFreeShippingCard = value.some( ( el ) => el.rate > 0 );

	let initialOfferFreeShippingValue = null;
	if ( displayOfferFreeShippingCard ) {
		initialOfferFreeShippingValue = value.some(
			( el ) => el.options?.free_shipping_threshold > 0
		)
			? 'yes'
			: 'no';
	}

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
			{ offerFreeShippingValue === 'yes' && <MinimumOrderCard /> }
		</>
	);
};

export default FlatShippingRatesInputCards;

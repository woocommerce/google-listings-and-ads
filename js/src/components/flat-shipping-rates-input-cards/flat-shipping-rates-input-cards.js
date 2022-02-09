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

	let initialOfferFreeShippingValue;
	if ( displayOfferFreeShippingCard ) {
		initialOfferFreeShippingValue = value.some(
			( el ) => el.options?.free_shipping_threshold > 0
		);
	}

	const [ offerFreeShippingValue, setOfferFreeShippingValue ] = useState(
		initialOfferFreeShippingValue
	);

	const handleOfferFreeShippingChange = ( newOfferFreeShippingValue ) => {
		/**
		 * When users select 'no', we remove all the
		 * shippingRate.options.free_shipping_threshold.
		 */
		if ( ! newOfferFreeShippingValue ) {
			const newValue = value.map( ( el ) => {
				return {
					...el,
					options: {
						...el.options,
						free_shipping_threshold: undefined,
					},
				};
			} );

			onChange( newValue );
		}

		setOfferFreeShippingValue( newOfferFreeShippingValue );
	};

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
					onChange={ handleOfferFreeShippingChange }
				/>
			) }
			{ offerFreeShippingValue && (
				<MinimumOrderCard value={ value } onChange={ onChange } />
			) }
		</>
	);
};

export default FlatShippingRatesInputCards;

/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import OfferFreeShippingCard from './offer-free-shipping-card';
import MinimumOrderCard from './minimum-order-card';

const FreeShippingCards = ( props ) => {
	const { value, onChange } = props;

	const initialOfferFreeShippingValue = value.some(
		( el ) => el.options.free_shipping_threshold > 0
	);

	const [ offerFreeShippingValue, setOfferFreeShippingValue ] = useState(
		initialOfferFreeShippingValue
	);

	const handleOfferFreeShippingChange = ( newOfferFreeShippingValue ) => {
		/**
		 * When users select 'No', we remove all the
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
			<OfferFreeShippingCard
				value={ offerFreeShippingValue }
				onChange={ handleOfferFreeShippingChange }
			/>
			{ offerFreeShippingValue && (
				<MinimumOrderCard value={ value } onChange={ onChange } />
			) }
		</>
	);
};

export default FreeShippingCards;

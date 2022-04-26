/**
 * Internal dependencies
 */
import isNonFreeShippingRate from '.~/utils/isNonFreeShippingRate';
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';
import OfferFreeShippingCard from './offer-free-shipping-card';
import MinimumOrderCard from './minimum-order-card';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries, formProps } = props;
	const { getInputProps, values, setValue } = formProps;
	const displayFreeShippingCards = values.shipping_country_rates.some(
		isNonFreeShippingRate
	);

	const getShippingRatesChangeHandler = ( onChange ) => (
		newShippingRates
	) => {
		/**
		 * If all the shipping rates are free shipping,
		 * we set the offer_free_shipping to undefined,
		 * so that when users add a non-free shipping rate,
		 * they would need to choose "yes" / "no" for offer_free_shipping.
		 */
		if ( ! newShippingRates.some( isNonFreeShippingRate ) ) {
			setValue( 'offer_free_shipping', undefined );
		}

		onChange( newShippingRates );
	};

	const getOfferFreeShippingChangeHandler = ( onChange ) => (
		newOfferFreeShippingValue
	) => {
		/**
		 * When users select 'No', we remove all the
		 * shippingRate.options.free_shipping_threshold.
		 */
		if ( ! newOfferFreeShippingValue ) {
			const newValue = values.shipping_country_rates.map( ( el ) => {
				return {
					...el,
					options: {
						...el.options,
						free_shipping_threshold: undefined,
					},
				};
			} );

			setValue( 'shipping_country_rates', newValue );
		}

		onChange( newOfferFreeShippingValue );
	};

	return (
		<>
			<EstimatedShippingRatesCard
				audienceCountries={ audienceCountries }
				{ ...getInputProps( 'shipping_country_rates' ) }
				onChange={ getShippingRatesChangeHandler(
					getInputProps( 'shipping_country_rates' ).onChange
				) }
			/>
			{ displayFreeShippingCards && (
				<>
					<OfferFreeShippingCard
						{ ...getInputProps( 'offer_free_shipping' ) }
						onChange={ getOfferFreeShippingChangeHandler(
							getInputProps( 'offer_free_shipping' ).onChange
						) }
					/>
					{ values.offer_free_shipping && (
						<MinimumOrderCard
							{ ...getInputProps( 'shipping_country_rates' ) }
						/>
					) }
				</>
			) }
		</>
	);
};

export default FlatShippingRatesInputCards;

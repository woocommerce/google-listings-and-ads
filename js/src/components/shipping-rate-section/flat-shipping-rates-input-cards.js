/**
 * Internal dependencies
 */
import isNonFreeShippingRate from '.~/utils/isNonFreeShippingRate';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';
import OfferFreeShippingCard from './offer-free-shipping-card';
import MinimumOrderCard from './minimum-order-card';

const FlatShippingRatesInputCards = ( props ) => {
	const { audienceCountries } = props;
	const { getInputProps, values } = useAdaptiveFormContext();
	const displayFreeShippingCards = values.shipping_country_rates.some(
		isNonFreeShippingRate
	);

	return (
		<>
			<EstimatedShippingRatesCard
				audienceCountries={ audienceCountries }
				{ ...getInputProps( 'shipping_country_rates' ) }
			/>
			{ displayFreeShippingCards && (
				<>
					<OfferFreeShippingCard
						{ ...getInputProps( 'offer_free_shipping' ) }
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

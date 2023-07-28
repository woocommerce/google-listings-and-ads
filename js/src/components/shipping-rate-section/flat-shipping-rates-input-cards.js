/**
 * Internal dependencies
 */
import isNonFreeShippingRate from '.~/utils/isNonFreeShippingRate';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import EstimatedShippingRatesCard from './estimated-shipping-rates-card';
import OfferFreeShippingCard from './offer-free-shipping-card';
import MinimumOrderCard from './minimum-order-card';

const FlatShippingRatesInputCards = () => {
	const { getInputProps, values, adapter } = useAdaptiveFormContext();
	const displayFreeShippingCards = values.shipping_country_rates.some(
		isNonFreeShippingRate
	);

	function getCardProps( key, validationKey = key ) {
		return {
			...getInputProps( key ),
			helper: adapter.renderRequestedValidation( validationKey ),
		};
	}

	return (
		<>
			<EstimatedShippingRatesCard
				audienceCountries={ adapter.audienceCountries }
				{ ...getCardProps( 'shipping_country_rates' ) }
			/>
			{ displayFreeShippingCards && (
				<>
					<OfferFreeShippingCard
						{ ...getCardProps( 'offer_free_shipping' ) }
					/>
					{ values.offer_free_shipping && (
						<MinimumOrderCard
							{ ...getCardProps(
								'shipping_country_rates',
								'free_shipping_threshold'
							) }
						/>
					) }
				</>
			) }
		</>
	);
};

export default FlatShippingRatesInputCards;

/**
 * Internal dependencies
 */
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AddRateButton from './add-rate-button';
import CountriesPriceInputForm from './countries-price-input-form';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import useShippingRatesWithSavedSuggestions from './useShippingRatesWithSavedSuggestions';
import groupShippingRatesByPriceCurrency from '.~/utils/groupShippingRatesByPriceCurrency';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import './index.scss';

const ShippingRateSetup = () => {
	const {
		loading: loadingShippingRates,
		data: dataShippingRates,
	} = useShippingRatesWithSavedSuggestions();
	const { code: currencyCode } = useStoreCurrency();
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( loadingShippingRates || ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	const expectedCountryCount = selectedCountryCodes.length;
	const actualCountryCount = dataShippingRates.length;
	const remainingCount = expectedCountryCount - actualCountryCount;

	const countriesPriceArray = groupShippingRatesByPriceCurrency(
		dataShippingRates
	);

	// Prefill to-be-added price.
	if ( countriesPriceArray.length === 0 ) {
		countriesPriceArray.push( {
			countries: selectedCountryCodes,
			price: null,
			currency: currencyCode,
		} );
	}

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<div className="countries-price">
					<VerticalGapLayout>
						{ countriesPriceArray.map( ( el ) => {
							return (
								<div
									key={ el.countries.join( '-' ) }
									className="countries-price-input-form"
								>
									<CountriesPriceInputForm
										savedValue={ el }
									/>
								</div>
							);
						} ) }
						{ actualCountryCount >= 1 && remainingCount >= 1 && (
							<div className="add-rate-button">
								<AddRateButton />
							</div>
						) }
					</VerticalGapLayout>
				</div>
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingRateSetup;

/**
 * Internal dependencies
 */
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AddRateButton from './add-rate-button';
import CountriesPriceInput from './countries-price-input';
import getCountriesPriceArray from '.~/utils/getCountriesPriceArray';

/**
 * Partial form to provide shipping rates for individual countries,
 * with an UI, that allows to aggregate countries with the same rate.
 *
 * @param {Object} props
 * @param {Array<ShippingRateFromServerSide>} props.value Array of individual shipping rates to be used as the initial values of the form.
 * @param {string} props.currencyCode Shop's currency code.
 * @param {Array<CountryCode>} props.audienceCountries Array of country codes of all audience countries.
 * @param {(newValue: Object) => void} props.onChange Callback called with new data once shipping rates are changed.
 */
export default function ShippingCountriesForm( {
	value: shippingRates,
	currencyCode,
	audienceCountries,
	onChange,
} ) {
	const actualCountryCount = shippingRates.length;
	const actualCountries = new Map(
		shippingRates.map( ( rate ) => [ rate.countryCode, rate ] )
	);
	const remainingCountryCodes = audienceCountries.filter(
		( el ) => ! actualCountries.has( el )
	);
	const remainingCount = remainingCountryCodes.length;
	// We may have shipping rates defined for more than the audience countries.
	// Therefore, the number of countries we anticipate is what we acutally have + missing audience ones.
	const totalCountyCount = actualCountryCount + remainingCount;

	// Group countries with the same rate.
	const countriesPriceArray = getCountriesPriceArray( shippingRates );

	// Prefill to-be-added price.
	if ( countriesPriceArray.length === 0 ) {
		countriesPriceArray.push( {
			countries: audienceCountries,
			price: null,
			currency: currencyCode,
		} );
	}

	// Given the limitations of `<Form>` component we can communicate up only onChange.
	// Therefore we loose the infromation whether it was add, change, delete.
	// In autosave/setup MC case, we would have to either re-calculate to deduct that information,
	// or fix that in `<Form>` component.
	function handleDelete( deletedCountries ) {
		onChange(
			shippingRates.filter(
				( rate ) => ! deletedCountries.includes( rate.countryCode )
			)
		);
	}
	function handleAdd( { countries, currency, rate } ) {
		// Split aggregated rate, to individial rates per country.
		const addedIndividualRates = countries.map( ( countryCode ) => ( {
			countryCode,
			currency,
			rate, // TODO: unify that
		} ) );

		onChange( shippingRates.concat( addedIndividualRates ) );
	}
	function handleChange(
		{ countries, currency, price },
		deletedCountries = []
	) {
		deletedCountries.forEach( ( countryCode ) =>
			actualCountries.delete( countryCode )
		);

		// Upsert rates.
		countries.forEach( ( countryCode ) => {
			actualCountries.set( countryCode, {
				countryCode,
				currency,
				rate: price, // TODO: unify that
			} );
		} );
		onChange( Array.from( actualCountries.values() ) );
	}

	return (
		<div className="countries-price">
			<VerticalGapLayout>
				{ countriesPriceArray.map( ( el ) => {
					return (
						<div
							key={ el.countries.join( '-' ) }
							className="countries-price-input-form"
						>
							<CountriesPriceInput
								value={ el }
								audienceCountries={ audienceCountries }
								totalCountyCount={ totalCountyCount }
								onChange={ handleChange }
								onDelete={ handleDelete }
							/>
						</div>
					);
				} ) }
				{ actualCountryCount >= 1 && remainingCount >= 1 && (
					<div className="add-rate-button">
						<AddRateButton
							countries={ remainingCountryCodes }
							onSubmit={ handleAdd }
						/>
					</div>
				) }
			</VerticalGapLayout>
		</div>
	);
}

/**
 * Individual shipping rate.
 *
 * @typedef {Object} ShippingRate
 * @property {CountryCode} countryCode Destination country code.
 * @property {string} currency Currency of the price.
 * @property {number} price Shipping price.
 */

/**
 * Aggregated shipping rate.
 *
 * @typedef {Object} AggregatedShippingRate
 * @property {Array<CountryCode>} countries Array of destination country codes.
 * @property {string} currency Currency of the price.
 * @property {number} price Shipping price.
 */

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRateFromServerSide
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

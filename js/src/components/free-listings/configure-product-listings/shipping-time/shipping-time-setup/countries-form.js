/**
 * Internal dependencies
 */
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AddTimeButton from './add-time-button';
import CountriesTimeInput from './countries-time-input';
import getCountriesTimeArray from './getCountriesTimeArray';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import(".~/data/actions").ShippingTime } ShippingTime
 * @typedef { import(".~/data/actions").AggregatedShippingTime } AggregatedShippingTime
 */

/**
 * Partial form to provide shipping times for individual countries,
 * with an UI, that allows to aggregate countries with the same time.
 *
 * @param {Object} props
 * @param {Array<ShippingTime>} props.value Array of individual shipping times to be used as the initial values of the form.
 * @param {Array<CountryCode>} props.selectedCountryCodes Array of country codes of all audience countries.
 * @param {(newValue: Object) => void} props.onChange Callback called with new data once shipping times are changed.
 */
export default function ShippingCountriesForm( {
	value: shippingTimes,
	selectedCountryCodes,
	onChange,
} ) {
	const actualCountryCount = shippingTimes.length;
	const actualCountries = new Map(
		shippingTimes.map( ( time ) => [ time.countryCode, time ] )
	);
	const remainingCountryCodes = selectedCountryCodes.filter(
		( el ) => ! actualCountries.has( el )
	);
	const remainingCount = remainingCountryCodes.length;

	// Group countries with the same time.
	const countriesTimeArray = getCountriesTimeArray( shippingTimes );

	// Prefill to-be-added time.
	if ( countriesTimeArray.length === 0 ) {
		countriesTimeArray.push( {
			countries: selectedCountryCodes,
			time: null,
		} );
	}

	// Given the limitations of `<Form>` component we can communicate up only onChange.
	// Therefore we loose the infromation whether it was add, change, delete.
	// In autosave/setup MC case, we would have to either re-calculate to deduct that information,
	// or fix that in `<Form>` component.
	function handleDelete( deletedCountries ) {
		onChange(
			shippingTimes.filter(
				( time ) => ! deletedCountries.includes( time.countryCode )
			)
		);
	}
	function handleAdd( { countries, time } ) {
		// Split aggregated time, to individial times per country.
		const addedIndividualTimes = countries.map( ( countryCode ) => ( {
			countryCode,
			time,
		} ) );

		onChange( shippingTimes.concat( addedIndividualTimes ) );
	}
	function handleChange( { countries, time }, deletedCountries = [] ) {
		deletedCountries.forEach( ( countryCode ) =>
			actualCountries.delete( countryCode )
		);

		// Upsert times.
		countries.forEach( ( countryCode ) => {
			actualCountries.set( countryCode, {
				countryCode,
				time,
			} );
		} );
		onChange( Array.from( actualCountries.values() ) );
	}

	return (
		<div className="countries-time">
			<VerticalGapLayout>
				{ countriesTimeArray.map( ( el ) => {
					return (
						<div
							key={ el.countries.join( '-' ) }
							className="countries-time-input-form"
						>
							<CountriesTimeInput
								value={ el }
								audienceCountries={ selectedCountryCodes }
								onChange={ handleChange }
								onDelete={ handleDelete }
							/>
						</div>
					);
				} ) }
				{ actualCountryCount >= 1 && remainingCount >= 1 && (
					<div className="add-time-button">
						<AddTimeButton
							countries={ remainingCountryCodes }
							onSubmit={ handleAdd }
						/>
					</div>
				) }
			</VerticalGapLayout>
		</div>
	);
}

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import SupportedCountrySelect from '~/components/supported-country-select';
import isNonFreeShippingRate from '~/utils/isNonFreeShippingRate';
import './audience-select-control.scss';

/**
 * Component for editing the primary market's audience (countries) in the Edit Market modal.
 */
const AudienceSelectControl = () => {
	const {
		getInputProps,
		values,
		setValues,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { code: storeCurrencyCode } = useStoreCurrency();

	const {
		shipping_country_rates: rawRates = [],
		shipping_country_times: rawTimes = [],
		flat_shipping_rate,
		flat_shipping_min_time,
		flat_shipping_max_time,
	} = values;

	const { onChange, ...inputProps } = getInputProps( 'countries' );

	const handleChange = ( audienceCountries ) => {
		onChange( audienceCountries );

		// Filter removed countries AND fill in newly added countries using the current flat rate.
		const filteredRates = rawRates.filter( ( rate ) =>
			audienceCountries.includes( rate.country )
		);
		const missingCountries = audienceCountries.filter(
			( country ) =>
				! filteredRates.some( ( rate ) => rate.country === country )
		);
		const existingThreshold = filteredRates.find( isNonFreeShippingRate )
			?.options?.free_shipping_threshold;
		const nextRates =
			flat_shipping_rate !== undefined && missingCountries.length > 0
				? [
						...filteredRates,
						...missingCountries.map( ( country ) => ( {
							options: {
								free_shipping_threshold: existingThreshold,
							},
							country,
							currency: storeCurrencyCode,
							rate: flat_shipping_rate,
						} ) ),
				  ]
				: filteredRates;

		// For times: filter removed countries AND add newly added countries.
		const filteredTimes = rawTimes.filter( ( time ) =>
			audienceCountries.includes( time.countryCode )
		);
		const missingTimesCountries = audienceCountries.filter(
			( country ) =>
				! filteredTimes.some( ( time ) => time.countryCode === country )
		);
		const nextTimes =
			flat_shipping_min_time !== null &&
			flat_shipping_max_time !== null &&
			missingTimesCountries.length > 0
				? [
						...filteredTimes,
						...missingTimesCountries.map( ( countryCode ) => ( {
							countryCode,
							time: flat_shipping_min_time,
							maxTime: flat_shipping_max_time,
						} ) ),
				  ]
				: filteredTimes;

		// `onChange` above satisfies the `getInputProps` contract and notifies external
		// listeners, but it internally calls `setValues( { countries } )` — a first synchronous
		// call against the current state snapshot.
		//
		// Due to a WC 6.9+ closure bug, a second synchronous `setValues` call merges against
		// that *same* original snapshot rather than the result of the first call. Batching all
		// derived fields into a single `setValues` call here ensures they land atomically on one
		// snapshot, avoiding the race. See adaptive-form.js `setValueCompatibly` for detail.
		setValues( {
			countries: audienceCountries,
			shipping_country_rates: nextRates,
			shipping_country_times: nextTimes,
		} );
	};

	return (
		<div className="gla-audience-select-control">
			<SupportedCountrySelect
				{ ...inputProps }
				onChange={ handleChange }
				help={ __(
					'Select which countries your store ships to.',
					'google-listings-and-ads'
				) }
				label={ __( 'Audience', 'google-listings-and-ads' ) }
				multiple
			/>

			{ renderRequestedValidation( 'countries' ) }
		</div>
	);
};

export default AudienceSelectControl;

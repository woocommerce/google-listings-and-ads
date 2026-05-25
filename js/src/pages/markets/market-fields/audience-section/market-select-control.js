/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import AppSelectControl from '~/components/app-select-control';
import usePrimaryMarketDetails from '~/hooks/usePrimaryMarketDetails';

/**
 * Renders the market select control within the market edit form.
 * The options for this control are derived from the primary market's countries,
 * which are fetched from the store; thus, this control is only rendered once
 * the relevant data has been resolved.
 */
const MarketSelectControl = () => {
	const {
		data: { countries },
		hasFinishedResolution: hasResolvedCountries,
	} = useAppSelectDispatch( 'getMCCountriesAndContinents' );
	const {
		data: primaryMarket,
		hasFinishedResolution: hasResolvedPrimaryMarket,
	} = usePrimaryMarketDetails();
	const {
		getInputProps,
		values,
		setValues,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { shipping_country_rates, shipping_country_times } = values;

	if ( ! hasResolvedCountries || ! hasResolvedPrimaryMarket ) {
		return null;
	}

	const options = [
		{ value: '', label: __( 'Select…', 'google-listings-and-ads' ) },
		...primaryMarket.countries.map( ( countryCode ) => ( {
			value: countryCode,
			label: countries[ countryCode ]?.name || countryCode,
		} ) ),
	];

	// Extract onBlur to avoid WC Form marking this field as "touched",
	// the validation error should only appear after the user clicks "Add market".
	const { onChange, onBlur, ...inputProps } = getInputProps( 'country' );

	const handleChange = ( selectedOption ) => {
		onChange( selectedOption );

		const existingRate = shipping_country_rates?.find(
			( rate ) => rate.country === selectedOption
		);
		const existingTime = shipping_country_times?.find(
			( time ) => time.countryCode === selectedOption
		);

		// `onChange` above satisfies the `getInputProps` contract and notifies any external
		// listeners, but it internally calls `setValues( { country } )` — a first synchronous
		// call against the current state snapshot.
		//
		// Due to a WC 6.9+ closure bug, a second synchronous `setValues` call merges against
		// that *same* original snapshot rather than the result of the first call. Omitting
		// `country` here would therefore revert it to its pre-selection value.
		//
		// Including `country` in this single batch call ensures all fields land atomically on
		// one snapshot, avoiding the race. See adaptive-form.js `setValueCompatibly` for detail.
		setValues( {
			country: selectedOption,
			...( existingRate && {
				flat_shipping_rate: existingRate.rate,
				offer_free_shipping:
					existingRate.options?.free_shipping_threshold > 0,
				free_shipping_threshold:
					existingRate.options?.free_shipping_threshold ?? undefined,
			} ),
			...( existingTime && {
				flat_shipping_min_time: existingTime.time,
				flat_shipping_max_time: existingTime.maxTime,
			} ),
		} );
	};

	const appSelectControlProps = {
		...inputProps,
		...( ! inputProps.selected
			? {
					autoSelectFirstOption: true,
					value: undefined,
			  }
			: {} ),
	};

	return (
		<div>
			<AppSelectControl
				label={ __( 'Market', 'google-listings-and-ads' ) }
				options={ options }
				onChange={ handleChange }
				{ ...appSelectControlProps }
			/>
			{ renderRequestedValidation( 'country' ) }
		</div>
	);
};

export default MarketSelectControl;

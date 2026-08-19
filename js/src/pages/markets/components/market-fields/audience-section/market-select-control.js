/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TreeSelect } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import useMarkets from '../../../hooks/useMarkets';
import usePrimaryMarketDetails from '../../../hooks/usePrimaryMarketDetails';

/**
 * Renders the market select control within the market edit form.
 * The options for this control are derived from the primary market's countries,
 * which are fetched from the store; thus, this control is only rendered once
 * the relevant data has been resolved.
 */
const MarketSelectControl = () => {
	const {
		data: { countries, continents },
		hasFinishedResolution: hasResolvedCountries,
	} = useAppSelectDispatch( 'getMCCountriesAndContinents' );
	const {
		data: primaryMarket,
		hasFinishedResolution: hasResolvedPrimaryMarket,
	} = usePrimaryMarketDetails();
	const { data: markets } = useMarkets();
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

	// Exclude countries already assigned to a market: the primary market's own
	// countries, as well as every secondary market's country.
	const usedCountries = new Set( [
		...primaryMarket.countries,
		...( markets
			?.filter( ( market ) => market.country )
			.map( ( market ) => market.country ) ?? [] ),
	] );

	const continentGroups = Object.values( continents ).reduce(
		( acc, continent ) => {
			const countryOptions = continent.countries
				.filter( ( countryCode ) => ! usedCountries.has( countryCode ) )
				.map( ( countryCode ) => ( {
					value: countryCode,
					label: countries[ countryCode ]?.name || countryCode,
				} ) );

			if ( countryOptions.length ) {
				acc.push( {
					label: continent.name,
					options: countryOptions,
				} );
			}

			return acc;
		},
		[]
	);

	const { onChange, value } = getInputProps( 'country' );

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

	return (
		<div>
			<TreeSelect
				label={ __( 'Market', 'google-listings-and-ads' ) }
				selectedId={ value ?? '' }
				onChange={ handleChange }
				__next40pxDefaultSize
			>
				<option value="">
					{ __( 'Select…', 'google-listings-and-ads' ) }
				</option>
				{ continentGroups.map(
					( { label, options: countryOptions } ) => (
						<optgroup key={ label } label={ label }>
							{ countryOptions.map(
								( {
									value: countryCode,
									label: countryName,
								} ) => (
									<option
										key={ countryCode }
										value={ countryCode }
									>
										{ countryName }
									</option>
								)
							) }
						</optgroup>
					)
				) }
			</TreeSelect>
			{ renderRequestedValidation( 'country' ) }
		</div>
	);
};

export default MarketSelectControl;

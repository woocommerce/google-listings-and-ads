/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
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
	const { getInputProps, values, setValues, isDirty } =
		useAdaptiveFormContext();
	const { country, shipping_country_rates, shipping_country_times } = values;

	const syncRef = useRef( null );
	syncRef.current = {
		shipping_country_rates,
		shipping_country_times,
		setValues,
		primaryMarket,
		isDirty,
	};

	useEffect( () => {
		if ( ! hasResolvedCountries || ! hasResolvedPrimaryMarket ) {
			return;
		}

		const effectiveCountry =
			country || syncRef.current.primaryMarket.countries[ 0 ];

		if ( ! effectiveCountry ) {
			return;
		}

		const { isDirty: dirty } = syncRef.current;
		const existingRate = dirty
			? undefined
			: syncRef.current.shipping_country_rates?.find(
					( rate ) => rate.country === effectiveCountry
			  );
		const existingTime = dirty
			? undefined
			: syncRef.current.shipping_country_times?.find(
					( time ) => time.countryCode === effectiveCountry
			  );

		syncRef.current.setValues( {
			...( ! country && { country: effectiveCountry } ),
			...( existingRate && {
				flat_shipping_rate: existingRate.rate,
				offer_free_shipping:
					existingRate.options?.free_shipping_threshold > 0,
				free_shipping_threshold:
					existingRate.options?.free_shipping_threshold ?? [],
			} ),
			...( existingTime && {
				flat_shipping_min_time: existingTime.time,
				flat_shipping_max_time: existingTime.maxTime,
			} ),
		} );
	}, [ country, hasResolvedCountries, hasResolvedPrimaryMarket ] );

	if ( ! hasResolvedCountries || ! hasResolvedPrimaryMarket ) {
		return null;
	}

	const options = primaryMarket.countries.map( ( countryCode ) => ( {
		value: countryCode,
		label: countries[ countryCode ]?.name || countryCode,
	} ) );

	const inputProps = getInputProps( 'country' );

	return (
		<AppSelectControl
			label={ __( 'Market', 'google-listings-and-ads' ) }
			options={ options }
			{ ...inputProps }
		/>
	);
};

export default MarketSelectControl;

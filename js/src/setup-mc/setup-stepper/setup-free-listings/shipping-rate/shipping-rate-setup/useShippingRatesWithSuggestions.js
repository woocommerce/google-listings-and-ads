/**
 * External dependencies
 */
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useShippingRates from '.~/hooks/useShippingRates';
import getCountriesPriceArray from './getCountriesPriceArray';
import { useAppDispatch } from '.~/data';
import useShippingRatesSuggestions from './useShippingRatesSuggestions';

const useShippingRatesWithSuggestions = () => {
	const [ saving, setSaving ] = useState( true );
	const { upsertShippingRates } = useAppDispatch();
	const {
		hasFinishedResolution: hfrShippingRates,
		data: dataShippingRates,
	} = useShippingRates();
	const {
		loading: loadingSuggestions,
		data: dataSuggestions,
	} = useShippingRatesSuggestions();

	/**
	 * `isInitialShippingRatesEmpty` is used to indicate
	 * whether the initial loaded shipping rates
	 * has a pre-saved value or not.
	 *
	 * If it does not have a pre-saved value,
	 * shipping rates should be an empty array,
	 * and we should save the suggestions as shipping rates.
	 *
	 * If it does have a pre-saved value,
	 * then we should not save the suggestions,
	 * even when users manually deleted all the pre-saved shipping rates value.
	 * The exception is when users deleted all the pre-saved value
	 * and then reload the page,
	 * then the suggestions would be saved as shipping rates as per above logic.
	 */
	const isInitialShippingRatesEmpty = useRef( undefined );
	if (
		hfrShippingRates &&
		isInitialShippingRatesEmpty.current === undefined
	) {
		isInitialShippingRatesEmpty.current = dataShippingRates.length === 0;
	}

	const saveSuggestions = useCallback(
		async ( suggestions ) => {
			const countriesPriceArray = getCountriesPriceArray( suggestions );
			const values = countriesPriceArray.map( ( el ) => ( {
				countryCodes: el.countries,
				currency: el.currency,
				rate: el.price,
			} ) );
			const promises = values.map( ( el ) => {
				return upsertShippingRates( el );
			} );
			await Promise.all( promises );
			setSaving( false );
		},
		[ upsertShippingRates ]
	);

	/**
	 * Used to track whether `saveSuggestions` has been called.
	 * We want to call the function one time only.
	 */
	const hasSavedSuggestions = useRef( false );

	/**
	 * Call `saveSuggestions` when:
	 *
	 * - there is no pre-saved initial shipping rates value.
	 * - we have suggestions data.
	 * - we have not saved the suggestions data as shipping rates.
	 */
	useEffect( () => {
		if (
			isInitialShippingRatesEmpty.current &&
			dataSuggestions &&
			hasSavedSuggestions.current === false
		) {
			hasSavedSuggestions.current = true;
			saveSuggestions( dataSuggestions );
		}
	}, [ dataSuggestions, saveSuggestions ] );

	return {
		loading:
			loadingSuggestions ||
			! hfrShippingRates ||
			( isInitialShippingRatesEmpty.current ? saving : false ),
		data: dataShippingRates,
	};
};

export default useShippingRatesWithSuggestions;

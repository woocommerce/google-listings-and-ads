/**
 * External dependencies
 */
import { useState, useRef, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useShippingRates from '.~/hooks/useShippingRates';
import useShippingRatesSuggestions from './useShippingRatesSuggestions';
import useSaveSuggestions from './useSaveSuggestions';
import useCallbackOnceEffect from '.~/hooks/useCallbackOnceEffect';

/**
 * @typedef {Object} ShippingRatesWithSavedSuggestionsResult
 * @property {boolean} loading Whether loading is in progress.
 * @property {Array<import('.~/data/actions').ShippingRate>?} data Shipping rates.
 */

/**
 * Check existing shipping rates, and if it is empty, get shipping rates suggestions
 * and save the suggestions as shipping rates.
 *
 * @return {ShippingRatesWithSavedSuggestionsResult} Result object with `loading` and `data`.
 */
const useShippingRatesWithSavedSuggestions = () => {
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

	/**
	 * `done` is used to indicate whether saving has finished.
	 * This is only used when we have no pre-saved initial shipping rates value
	 * and we call `saveSuggestions`. It is initially set to `false`,
	 * and will be set to `true` after the suggestions are saved.
	 */
	const [ done, setDone ] = useState( false );
	const saveSuggestions = useSaveSuggestions();
	const callSaveSuggestions = useCallback(
		async ( suggestions ) => {
			await saveSuggestions( suggestions );
			setDone( true );
		},
		[ saveSuggestions ]
	);

	/**
	 * `callSaveSuggestions` when:
	 *
	 * - there is no pre-saved initial shipping rates value.
	 * - we have suggestions data.
	 */
	useCallbackOnceEffect(
		isInitialShippingRatesEmpty.current && dataSuggestions,
		callSaveSuggestions,
		dataSuggestions
	);

	return {
		loading:
			loadingSuggestions ||
			! hfrShippingRates ||
			( isInitialShippingRatesEmpty.current && ! done ),
		data: dataShippingRates,
	};
};

export default useShippingRatesWithSavedSuggestions;

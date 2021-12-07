/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useApiFetchEffect from '.~/hooks/useApiFetchEffect';

/**
 * Automatically set `location` to be `selected`
 * and `countries` to be the value from Target Audience Suggestion API
 * when `values.location === null && values.countries.length === 0`
 * (i.e. when users first visit the page).
 *
 * The value setting is done by calling `onChange` from `getInputProps`,
 * simulating user's manual input action.
 *
 * @param {Object} formProps formProps.
 */
const useAutoSetLocationCountriesEffect = ( formProps ) => {
	const { values, getInputProps } = formProps;
	const { data } = useApiFetchEffect( {
		path: `wc/gla/mc/target_audience/suggestions`,
	} );

	const setLocationCountries = useCallback( () => {
		if ( ! data ) {
			return;
		}

		const countriesValue = data.countries;

		getInputProps( 'location' ).onChange( 'selected' );
		getInputProps( 'countries' ).onChange( countriesValue );
	}, [ data, getInputProps ] );

	const hasNoLocationCountries =
		values.location === null && values.countries.length === 0;

	useEffect( () => {
		if ( hasNoLocationCountries ) {
			setLocationCountries();
		}
	}, [ hasNoLocationCountries, setLocationCountries ] );
};

export default useAutoSetLocationCountriesEffect;

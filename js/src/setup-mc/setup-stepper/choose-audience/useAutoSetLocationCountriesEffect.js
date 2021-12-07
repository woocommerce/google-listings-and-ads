/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

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

	const setLocationCountries = useCallback( async () => {
		const data = await apiFetch( {
			path: `wc/gla/mc/target_audience/suggestions`,
		} );

		getInputProps( 'location' ).onChange( 'selected' );
		getInputProps( 'countries' ).onChange( data.countries );
	}, [ getInputProps ] );

	const hasNoLocationCountries =
		values.location === null && values.countries.length === 0;

	useEffect( () => {
		if ( hasNoLocationCountries ) {
			setLocationCountries();
		}
	}, [ hasNoLocationCountries, setLocationCountries ] );
};

export default useAutoSetLocationCountriesEffect;

/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useStoreCountry from '.~/hooks/useStoreCountry';

/**
 * Automatically set `location` to be `selected` and `countries` to be `[ storeCountryCode ]`
 * when `values.location === null && values.countries.length === 0` (i.e. when users first visit the page).
 *
 * This is done by calling `onChange` from `getInputProps`, simulating user's manual input action.
 *
 * @param {Object} formProps formProps.
 */
const useAutoSetLocationCountriesEffect = ( formProps ) => {
	const { values, getInputProps } = formProps;
	const { code: storeCountryCode } = useStoreCountry();

	const hasNoLocationCountries =
		values.location === null && values.countries.length === 0;

	const setLocationCountries = useCallback( () => {
		getInputProps( 'location' ).onChange( 'selected' );
		getInputProps( 'countries' ).onChange( [ storeCountryCode ] );
	}, [ getInputProps, storeCountryCode ] );

	useEffect( () => {
		if ( hasNoLocationCountries ) {
			setLocationCountries();
		}
	}, [ hasNoLocationCountries, setLocationCountries ] );
};

export default useAutoSetLocationCountriesEffect;

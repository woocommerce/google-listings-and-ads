/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useStoreCountry from '.~/hooks/useStoreCountry';
import useGetCountries from '.~/hooks/useGetCountries';

/**
 * Automatically set `location` to be `selected` and `countries` to be `[ storeCountryCode ]`
 * when `values.location === null && values.countries.length === 0` (i.e. when users first visit the page).
 *
 * If the `storeCountryCode` is not in the list of the MC supported countries, then `countries` would be set to empty array `[]`.
 *
 * This is done by calling `onChange` from `getInputProps`, simulating user's manual input action.
 *
 * @param {Object} formProps formProps.
 */
const useAutoSetLocationCountriesEffect = ( formProps ) => {
	const { values, getInputProps } = formProps;
	const { code: storeCountryCode } = useStoreCountry();
	const { data } = useGetCountries();

	const hasNoLocationCountries =
		values.location === null && values.countries.length === 0;

	const setLocationCountries = useCallback( () => {
		if ( ! data ) {
			return;
		}

		const countriesValue = data[ storeCountryCode ]
			? [ storeCountryCode ]
			: [];

		getInputProps( 'location' ).onChange( 'selected' );
		getInputProps( 'countries' ).onChange( countriesValue );
	}, [ data, getInputProps, storeCountryCode ] );

	useEffect( () => {
		if ( hasNoLocationCountries ) {
			setLocationCountries();
		}
	}, [ hasNoLocationCountries, setLocationCountries ] );
};

export default useAutoSetLocationCountriesEffect;

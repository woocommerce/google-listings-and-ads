/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Call `useAppSelectDispatch` with `"getAllowedCountries"`
 *
 * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
 */
const useGetAllowedCountries = () => {
	return useAppSelectDispatch( 'getAllowedCountries' );
};

export default useGetAllowedCountries;
